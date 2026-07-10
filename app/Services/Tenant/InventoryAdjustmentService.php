<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\InventoryAdjustmentItemAction;
use App\Enums\Tenant\InventoryAdjustmentStatus;
use App\Enums\Tenant\InventoryMovementType;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\InventoryAdjustmentItem;
use App\Models\Tenant\Media;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages multi-line inventory adjustments with transactional stock updates.
 */
class InventoryAdjustmentService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, InventoryAdjustment>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryAdjustment::query()
            ->with(['warehouse', 'creator'])
            ->withCount('items')
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): InventoryAdjustment
    {
        return InventoryAdjustment::query()
            ->with([
                'warehouse',
                'creator',
                'media',
                'items.product',
                'items.variant',
                'items.inventory',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data): InventoryAdjustment
    {
        return DB::transaction(function () use ($data): InventoryAdjustment {
            $warehouseId = (int) $data['warehouse_id'];
            $items = $data['items'] ?? [];

            if ($items === []) {
                throw new DomainException('At least one product line is required.');
            }

            $this->validateAttachment((int) ($data['media_id'] ?? 0) ?: null);

            /** @var InventoryAdjustment $adjustment */
            $adjustment = InventoryAdjustment::query()->create([
                'adjustment_number' => $this->generateAdjustmentNumber(),
                'warehouse_id' => $warehouseId,
                'status' => InventoryAdjustmentStatus::Posted,
                'reference_number' => $data['reference_number'] ?? null,
                'reason' => $data['reason'] ?? null,
                'media_id' => $data['media_id'] ?? null,
                'created_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $totalQuantityAdjusted = 0;

            foreach ($items as $index => $itemData) {
                $line = $this->createAndApplyItem($adjustment, $warehouseId, $itemData, (int) $index);
                $totalQuantityAdjusted += abs($line->quantity_change);
            }

            $adjustment->update([
                'total_products' => $adjustment->items()->count(),
                'total_quantity_adjusted' => $totalQuantityAdjusted,
            ]);

            return $this->find($adjustment->id);
        });
    }

    public function delete(InventoryAdjustment $adjustment): void
    {
        if ($adjustment->status !== InventoryAdjustmentStatus::Cancelled) {
            throw new DomainException('Only cancelled adjustments can be deleted.');
        }

        $adjustment->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            $adjustment = InventoryAdjustment::query()->find($id);

            if (! $adjustment instanceof InventoryAdjustment) {
                continue;
            }

            if ($adjustment->status === InventoryAdjustmentStatus::Cancelled) {
                $adjustment->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @return array{total: int, posted: int, cancelled: int, quantity_adjusted: int}
     */
    public function statistics(): array
    {
        return [
            'total' => InventoryAdjustment::query()->count(),
            'posted' => InventoryAdjustment::query()->where('status', InventoryAdjustmentStatus::Posted)->count(),
            'cancelled' => InventoryAdjustment::query()->where('status', InventoryAdjustmentStatus::Cancelled)->count(),
            'quantity_adjusted' => (int) InventoryAdjustment::query()
                ->where('status', InventoryAdjustmentStatus::Posted)
                ->sum('total_quantity_adjusted'),
        ];
    }

    /**
     * Search products for adjustment line entry at a warehouse.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function searchProducts(int $warehouseId, ?string $search = null, int $limit = 20): Collection
    {
        return Product::query()
            ->with([
                'defaultVariant' => fn ($query) => $query->with([
                    'inventories' => fn ($inventoryQuery) => $inventoryQuery->where('warehouse_id', $warehouseId),
                ]),
            ])
            ->when($search, function ($query, string $term): void {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhereHas('defaultVariant', function ($variantQuery) use ($term): void {
                            $variantQuery->where('sku', 'like', "%{$term}%")
                                ->orWhere('barcode', 'like', "%{$term}%");
                        });
                });
            })
            ->whereHas('defaultVariant')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) use ($warehouseId): array {
                $variant = $product->defaultVariant;
                $inventory = $variant?->inventories->firstWhere('warehouse_id', $warehouseId);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_variant_id' => $variant?->id,
                    'sku' => $variant?->sku,
                    'cost_price' => $variant?->cost_price,
                    'quantity_on_hand' => $inventory?->quantity ?? 0,
                    'inventory_id' => $inventory?->id,
                ];
            })
            ->filter(fn (array $row): bool => $row['product_variant_id'] !== null)
            ->values();
    }

    /**
     * @param array<string, mixed> $itemData
     * @throws Throwable
     */
    private function createAndApplyItem(
        InventoryAdjustment $adjustment,
        int $warehouseId,
        array $itemData,
        int $sortOrder
    ): InventoryAdjustmentItem {
        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail((int) $itemData['product_variant_id']);

        if ((int) $variant->product_id !== (int) ($itemData['product_id'] ?? $variant->product_id)) {
            throw new DomainException('Product variant does not belong to the selected product.');
        }

        $action = $itemData['action'] instanceof InventoryAdjustmentItemAction
            ? $itemData['action']
            : InventoryAdjustmentItemAction::from((string) $itemData['action']);

        $quantity = (int) $itemData['quantity'];

        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        $quantityChange = $action->signedQuantity($quantity);

        $inventory = Inventory::query()->firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'incoming_quantity' => 0,
                'damaged_quantity' => 0,
            ],
        );

        $quantityBefore = $inventory->quantity;

        if ($quantityChange < 0 && $inventory->availableQuantity() < abs($quantityChange)) {
            throw new DomainException(
                "Insufficient stock for {$variant->product->name}. Available: {$inventory->availableQuantity()}."
            );
        }

        $this->inventoryService->adjust(
            $inventory,
            $quantityChange,
            InventoryMovementType::Adjustment->value,
            InventoryAdjustment::class,
            $adjustment->id,
            $adjustment->reason,
        );

        $inventory->refresh();

        return InventoryAdjustmentItem::query()->create([
            'inventory_adjustment_id' => $adjustment->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'action' => $action,
            'quantity' => $quantity,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $inventory->quantity,
            'unit_cost' => $variant->cost_price,
            'sort_order' => $sortOrder,
        ]);
    }

    private function generateAdjustmentNumber(): string
    {
        $latest = InventoryAdjustment::query()
            ->withTrashed()
            ->where('adjustment_number', 'like', 'ADJ-%')
            ->orderByDesc('id')
            ->value('adjustment_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/ADJ-(\d+)/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return 'ADJ-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function validateAttachment(?int $mediaId): void
    {
        if ($mediaId === null) {
            return;
        }

        $media = Media::query()->find($mediaId);

        if (! $media instanceof Media) {
            throw new DomainException('Attachment media record was not found.');
        }

        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (! in_array((string) $media->mime_type, $allowedMimeTypes, true)) {
            throw new DomainException('Attachment must be a PDF or Word document.');
        }
    }
}
