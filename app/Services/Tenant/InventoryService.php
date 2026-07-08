<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\InventoryMovementType;
use App\Events\Tenant\StockLow;
use App\Events\Tenant\VariantBackInStock;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\ProductStockAlert;
use App\Models\Tenant\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Manages variant stock levels per warehouse within a tenant store.
 */
class InventoryService
{
    /**
     * Paginate inventory records.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Inventory>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Inventory::query()
            ->with(['warehouse', 'variant.product'])
            ->filter($filters)
            ->latest('updated_at')
            ->paginate($perPage);
    }

    /**
     * Find an inventory record by ID.
     *
     * @param int $id
     * @return Inventory
     */
    public function find(int $id): Inventory
    {
        return Inventory::query()
            ->with(['warehouse', 'variant.product', 'movements' => fn ($query) => $query->latest()->limit(10)])
            ->findOrFail($id);
    }

    /**
     * Update an inventory record.
     *
     * @param Inventory $inventory
     * @param  array<string, mixed>  $data
     * @return Inventory
     */
    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update([
            'reorder_level' => $data['reorder_level'] ?? $inventory->reorder_level,
            'reorder_quantity' => $data['reorder_quantity'] ?? $inventory->reorder_quantity,
            'incoming_quantity' => $data['incoming_quantity'] ?? $inventory->incoming_quantity,
            'damaged_quantity' => $data['damaged_quantity'] ?? $inventory->damaged_quantity,
            'location_code' => $data['location_code'] ?? $inventory->location_code,
            'batch_number' => $data['batch_number'] ?? $inventory->batch_number,
            'expiry_date' => $data['expiry_date'] ?? $inventory->expiry_date,
        ]);

        return $inventory->fresh(['warehouse', 'variant.product']);
    }

    /**
     * Upsert inventory for a variant at a warehouse.
     *
     * @param ProductVariant $variant
     * @param int $warehouseId
     * @param  array<string, mixed>  $data
     * @return Inventory
     */
    public function upsertForVariant(ProductVariant $variant, int $warehouseId, array $data): Inventory
    {
        $existing = Inventory::query()
            ->where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($existing instanceof Inventory) {
            $quantity = isset($data['quantity']) ? (int) $data['quantity'] : $existing->quantity;

            if ($quantity !== $existing->quantity) {
                $delta = $quantity - $existing->quantity;

                return $this->adjust(
                    $existing,
                    $delta,
                    InventoryMovementType::Adjustment->value,
                    reason: 'Inventory quantity updated',
                );
            }

            return $this->update($existing, $data);
        }

        $inventory = Inventory::query()->create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouseId,
            'quantity' => 0,
            'reserved_quantity' => (int) ($data['reserved_quantity'] ?? 0),
            'incoming_quantity' => (int) ($data['incoming_quantity'] ?? 0),
            'damaged_quantity' => (int) ($data['damaged_quantity'] ?? 0),
            'reorder_level' => isset($data['reorder_level'])
                ? (int) $data['reorder_level']
                : (isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null),
            'reorder_quantity' => isset($data['reorder_quantity']) ? (int) $data['reorder_quantity'] : null,
            'location_code' => $data['location_code'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        $initialQuantity = (int) ($data['quantity'] ?? 0);

        if ($initialQuantity > 0) {
            $this->adjust(
                $inventory,
                $initialQuantity,
                InventoryMovementType::Initial->value,
                reason: 'Initial stock level',
            );
        }

        return $inventory->fresh(['warehouse', 'variant']);
    }

    /**
     * Adjust inventory quantity and record a movement.
     *
     * @param Inventory $inventory
     * @param int $quantityDelta
     * @param string $type
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $reason
     * @return Inventory
     * @throws Throwable
     */
    public function adjust(
        Inventory $inventory,
        int $quantityDelta,
        string $type = 'adjustment',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null,
    ): Inventory {
        return DB::transaction(function () use ($inventory, $quantityDelta, $type, $referenceType, $referenceId, $reason): Inventory {
            $inventory->refresh();
            $availableBefore = $inventory->availableQuantity();

            $inventory->adjust(
                $quantityDelta,
                $type,
                $referenceType,
                $referenceId,
                $reason,
                Auth::id(),
            );

            $this->evaluateStockChanges($inventory, $availableBefore);

            return $inventory->fresh(['warehouse', 'variant.product']);
        });
    }

    /**
     * Transfer stock between warehouses for the same variant.
     *
     * @param Inventory $source
     * @param int $destinationWarehouseId
     * @param int $quantity
     * @param string|null $reason
     * @return array
     * @throws Throwable
     */
    public function transfer(
        Inventory $source,
        int $destinationWarehouseId,
        int $quantity,
        ?string $reason = null,
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('Transfer quantity must be greater than zero.');
        }

        if ($source->availableQuantity() < $quantity) {
            throw new RuntimeException('Insufficient available inventory for transfer.');
        }

        if ($source->warehouse_id === $destinationWarehouseId) {
            throw new RuntimeException('Source and destination warehouses must be different.');
        }

        return DB::transaction(function () use ($source, $destinationWarehouseId, $quantity, $reason): array {
            $source->refresh();

            $this->adjust(
                $source,
                -$quantity,
                InventoryMovementType::Transfer->value,
                Inventory::class,
                $source->id,
                $reason ?? 'Stock transfer out',
            );

            $destination = $this->upsertForVariant(
                $source->variant,
                $destinationWarehouseId,
                ['quantity' => 0],
            );

            $destination = $this->adjust(
                $destination,
                $quantity,
                InventoryMovementType::Transfer->value,
                Inventory::class,
                $source->id,
                $reason ?? 'Stock transfer in',
            );

            return [
                'source' => $source->fresh(['warehouse', 'variant.product']),
                'destination' => $destination,
            ];
        });
    }

    /**
     * Reserve inventory quantity.
     *
     * @param Inventory $inventory
     * @param int $quantity
     * @return Inventory
     * @throws RuntimeException|Throwable
     */
    public function reserve(Inventory $inventory, int $quantity): Inventory
    {
        return DB::transaction(function () use ($inventory, $quantity): Inventory {
            $inventory->refresh();

            if (! $inventory->reserve($quantity)) {
                throw new RuntimeException('Insufficient available inventory.');
            }

            $inventory->movements()->create([
                'quantity_change' => 0,
                'quantity_before' => $inventory->quantity,
                'quantity_after' => $inventory->quantity,
                'type' => InventoryMovementType::Reservation->value,
                'reason' => "Reserved {$quantity} units",
                'created_by' => Auth::id(),
            ]);

            return $inventory->fresh(['warehouse', 'variant.product']);
        });
    }

    /**
     * Release reserved inventory quantity.
     *
     * @param Inventory $inventory
     * @param int $quantity
     * @return Inventory
     * @throws Throwable
     */
    public function release(Inventory $inventory, int $quantity): Inventory
    {
        return DB::transaction(function () use ($inventory, $quantity): Inventory {
            $inventory->refresh();
            $inventory->release($quantity);

            $inventory->movements()->create([
                'quantity_change' => 0,
                'quantity_before' => $inventory->quantity,
                'quantity_after' => $inventory->quantity,
                'type' => InventoryMovementType::Release->value,
                'reason' => "Released {$quantity} reserved units",
                'created_by' => Auth::id(),
            ]);

            return $inventory->fresh(['warehouse', 'variant.product']);
        });
    }

    /**
     * Paginate inventory movements.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, InventoryMovement>
     */
    public function paginateMovements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryMovement::query()
            ->with(['inventory.warehouse', 'inventory.variant.product', 'creator'])
            ->when(! empty($filters['inventory_id']), fn ($query) => $query->where(
                'inventory_id',
                (int) $filters['inventory_id'],
            ))
            ->when(! empty($filters['product_variant_id']), function ($query) use ($filters): void {
                $query->whereHas('inventory', fn ($inventoryQuery) => $inventoryQuery->where(
                    'product_variant_id',
                    (int) $filters['product_variant_id'],
                ));
            })
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get inventory statistics.
     *
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total_records' => Inventory::query()->count(),
            'low_stock' => Inventory::query()->lowStock()->count(),
            'out_of_stock' => Inventory::query()->whereRaw('(quantity - reserved_quantity) <= 0')->count(),
            'pending_stock_alerts' => ProductStockAlert::query()->pending()->count(),
        ];
    }

    /**
     * Paginate stock alerts.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, ProductStockAlert>
     */
    public function paginateStockAlerts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ProductStockAlert::query()
            ->with(['variant.product', 'customer'])
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get inventories for a variant.
     *
     * @param ProductVariant $variant
     * @return Collection<int, Inventory>
     */
    public function inventoriesForVariant(ProductVariant $variant): Collection
    {
        return Inventory::query()
            ->with('warehouse')
            ->where('product_variant_id', $variant->id)
            ->orderBy('warehouse_id')
            ->get();
    }

    /**
     * Dispatch inventory threshold events after a quantity change.
     *
     * @param Inventory $inventory
     * @param int $availableBefore
     * @return void
     */
    private function evaluateStockChanges(Inventory $inventory, int $availableBefore): void
    {
        $inventory->refresh();
        $availableAfter = $inventory->availableQuantity();
        $wasLowStock = $inventory->reorder_level !== null
            && $availableBefore <= $inventory->reorder_level;

        if (! $wasLowStock && $inventory->isLowStock()) {
            StockLow::dispatch($inventory);
        }

        if ($availableBefore <= 0 && $availableAfter > 0) {
            VariantBackInStock::dispatch($inventory);
        }
    }
}
