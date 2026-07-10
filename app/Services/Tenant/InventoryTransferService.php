<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\InventoryTransferStatus;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryTransfer;
use App\Models\Tenant\InventoryTransferItem;
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
 * Manages multi-line inventory transfers between warehouses.
 */
class InventoryTransferService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, InventoryTransfer>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->withCount('items')
            ->filter($filters)
            ->latest('transfer_date')
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): InventoryTransfer
    {
        return InventoryTransfer::query()
            ->with([
                'fromWarehouse',
                'toWarehouse',
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
    public function create(array $data): InventoryTransfer
    {
        return DB::transaction(function () use ($data): InventoryTransfer {
            $fromWarehouseId = (int) $data['from_warehouse_id'];
            $toWarehouseId = (int) $data['to_warehouse_id'];
            $items = $data['items'] ?? [];

            if ($fromWarehouseId === $toWarehouseId) {
                throw new DomainException('Source and destination warehouses must be different.');
            }

            if ($items === []) {
                throw new DomainException('At least one product line is required.');
            }

            $this->validateAttachment((int) ($data['media_id'] ?? 0) ?: null);

            $status = $data['status'] instanceof InventoryTransferStatus
                ? $data['status']
                : InventoryTransferStatus::from((string) $data['status']);

            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            $lineSubtotal = 0.0;
            $totalQuantity = 0;

            /** @var InventoryTransfer $transfer */
            $transfer = InventoryTransfer::query()->create([
                'transfer_number' => $this->generateTransferNumber(),
                'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => $status,
                'shipping_cost' => $shippingCost,
                'subtotal' => 0,
                'grand_total' => 0,
                'email_sent' => (bool) ($data['email_sent'] ?? false),
                'reason' => $data['reason'] ?? null,
                'media_id' => $data['media_id'] ?? null,
                'created_by' => Auth::id(),
                'completed_at' => $status->appliesStockMovement() ? now() : null,
            ]);

            foreach ($items as $index => $itemData) {
                $line = $this->createItem($transfer, $fromWarehouseId, $toWarehouseId, $itemData, (int) $index, $status);
                $lineSubtotal += (float) $line->subtotal;
                $totalQuantity += $line->quantity;
            }

            $grandTotal = $lineSubtotal + $shippingCost;

            $transfer->update([
                'subtotal' => $lineSubtotal,
                'grand_total' => $grandTotal,
                'total_products' => $transfer->items()->count(),
                'total_quantity_transferred' => $totalQuantity,
            ]);

            return $this->find($transfer->id);
        });
    }

    public function delete(InventoryTransfer $transfer): void
    {
        if ($transfer->status !== InventoryTransferStatus::Pending) {
            throw new DomainException('Only pending transfers can be deleted.');
        }

        $transfer->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            $transfer = InventoryTransfer::query()->find($id);

            if (! $transfer instanceof InventoryTransfer) {
                continue;
            }

            if ($transfer->status === InventoryTransferStatus::Pending) {
                $transfer->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @return array{total: int, pending: int, sent: int, completed: int, quantity_transferred: int}
     */
    public function statistics(): array
    {
        return [
            'total' => InventoryTransfer::query()->count(),
            'pending' => InventoryTransfer::query()->where('status', InventoryTransferStatus::Pending)->count(),
            'sent' => InventoryTransfer::query()->where('status', InventoryTransferStatus::Sent)->count(),
            'completed' => InventoryTransfer::query()->where('status', InventoryTransferStatus::Completed)->count(),
            'quantity_transferred' => (int) InventoryTransfer::query()
                ->whereIn('status', [
                    InventoryTransferStatus::Sent->value,
                    InventoryTransferStatus::Completed->value,
                ])
                ->sum('total_quantity_transferred'),
        ];
    }

    /**
     * Search products with stock at the source warehouse.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function searchProducts(int $fromWarehouseId, ?string $search = null, int $limit = 20): Collection
    {
        return Product::query()
            ->with([
                'defaultVariant' => fn ($query) => $query->with([
                    'inventories' => fn ($inventoryQuery) => $inventoryQuery->where('warehouse_id', $fromWarehouseId),
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
            ->map(function (Product $product) use ($fromWarehouseId): array {
                $variant = $product->defaultVariant;
                $inventory = $variant?->inventories->firstWhere('warehouse_id', $fromWarehouseId);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_variant_id' => $variant?->id,
                    'sku' => $variant?->sku,
                    'cost_price' => $variant?->cost_price,
                    'quantity_on_hand' => $inventory?->quantity ?? 0,
                    'available_quantity' => $inventory?->availableQuantity() ?? 0,
                    'inventory_id' => $inventory?->id,
                ];
            })
            ->filter(fn (array $row): bool => $row['product_variant_id'] !== null)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    private function createItem(
        InventoryTransfer $transfer,
        int $fromWarehouseId,
        int $toWarehouseId,
        array $itemData,
        int $sortOrder,
        InventoryTransferStatus $status,
    ): InventoryTransferItem {
        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail((int) $itemData['product_variant_id']);

        if ((int) $variant->product_id !== (int) ($itemData['product_id'] ?? $variant->product_id)) {
            throw new DomainException('Product variant does not belong to the selected product.');
        }

        $quantity = (int) $itemData['quantity'];

        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        $unitCost = (float) ($itemData['unit_cost'] ?? $variant->cost_price ?? 0);
        $taxRate = (float) ($itemData['tax_rate'] ?? 0);
        $lineBase = $unitCost * $quantity;
        $taxAmount = round($lineBase * ($taxRate / 100), 4);
        $subtotal = round($lineBase + $taxAmount, 4);

        $inventory = Inventory::query()->firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $fromWarehouseId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'incoming_quantity' => 0,
                'damaged_quantity' => 0,
            ],
        );

        if ($status->appliesStockMovement()) {
            if ($inventory->availableQuantity() < $quantity) {
                throw new DomainException(
                    "Insufficient stock for {$variant->product->name}. Available: {$inventory->availableQuantity()}."
                );
            }

            $this->inventoryService->transfer(
                $inventory,
                $toWarehouseId,
                $quantity,
                $transfer->reason ?? "Transfer {$transfer->transfer_number}",
                InventoryTransfer::class,
                $transfer->id,
            );
        }

        return InventoryTransferItem::query()->create([
            'inventory_transfer_id' => $transfer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'sort_order' => $sortOrder,
        ]);
    }

    private function generateTransferNumber(): string
    {
        $latest = InventoryTransfer::query()
            ->withTrashed()
            ->where('transfer_number', 'like', 'TRF-%')
            ->orderByDesc('id')
            ->value('transfer_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/TRF-(\d+)/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return 'TRF-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
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
