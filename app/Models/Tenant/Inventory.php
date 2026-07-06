<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Stock level for a product variant at a warehouse.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $warehouse_id
 * @property int $quantity
 * @property int $reserved_quantity
 * @property int $incoming_quantity
 * @property int $damaged_quantity
 * @property int $available_quantity
 * @property int|null $reorder_level
 * @property int|null $reorder_quantity
 * @property string|null $location_code
 * @property string|null $batch_number
 * @property Carbon|null $expiry_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductVariant $variant
 * @property-read Warehouse $warehouse
 * @property-read EloquentCollection<int, InventoryMovement> $movements
 */
class Inventory extends Model
{
    use LogsActivity;

    protected $table = 'inventories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'incoming_quantity',
        'damaged_quantity',
        'reorder_level',
        'reorder_quantity',
        'location_code',
        'batch_number',
        'expiry_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'incoming_quantity' => 'integer',
            'damaged_quantity' => 'integer',
            'reorder_level' => 'integer',
            'reorder_quantity' => 'integer',
            'expiry_date' => 'date',
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'quantity',
                'reserved_quantity',
                'incoming_quantity',
                'damaged_quantity',
                'reorder_level',
                'reorder_quantity',
                'warehouse_id',
                'location_code',
                'batch_number',
                'expiry_date',
            ])
            ->logOnlyDirty();
    }

    /**
     * Get the variant associated with this inventory.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the warehouse storing this inventory.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Stock movement history for this inventory.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Calculate the currently available quantity.
     */
    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Determine if the stock is below or equal to the reorder level.
     */
    public function isLowStock(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $this->availableQuantity() <= $this->reorder_level;
    }

    /**
     * Available quantity as an accessor alias.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->availableQuantity();
    }

    /**
     * Determine if stock is low as an accessor alias.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->isLowStock();
    }

    /**
     * Adjust stock and record a movement.
     */
    public function adjust(
        int $change,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null,
        ?int $createdBy = null,
    ): InventoryMovement {
        $quantityBefore = $this->quantity;
        $this->quantity = max(0, $this->quantity + $change);
        $this->save();

        return $this->movements()->create([
            'quantity_change' => $change,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Reserve stock for an order or hold.
     */
    public function reserve(int $quantity): bool
    {
        if ($this->availableQuantity() < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->save();

        return true;
    }

    /**
     * Release previously reserved stock.
     */
    public function release(int $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();
    }

    /**
     * @param  Builder<Inventory>  $query
     * @return Builder<Inventory>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->whereNotNull('reorder_level')
            ->whereRaw('(quantity - reserved_quantity) <= reorder_level');
    }

    /**
     * @param  Builder<Inventory>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Inventory>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['product_variant_id']), fn (Builder $q) => $q->where(
                'product_variant_id',
                (int) $filters['product_variant_id'],
            ))
            ->when(! empty($filters['warehouse_id']), fn (Builder $q) => $q->where(
                'warehouse_id',
                (int) $filters['warehouse_id'],
            ))
            ->when(! empty($filters['product_id']), function (Builder $q) use ($filters): void {
                $q->whereHas('variant', fn (Builder $variantQuery) => $variantQuery->where(
                    'product_id',
                    (int) $filters['product_id'],
                ));
            })
            ->when(filter_var($filters['low_stock'] ?? false, FILTER_VALIDATE_BOOLEAN), function (Builder $q): void {
                $q->lowStock();
            })
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where(function (Builder $nested) use ($search): void {
                    $nested->where('location_code', 'like', "%{$search}%")
                        ->orWhere('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('variant', fn (Builder $variantQuery) => $variantQuery
                            ->where('sku', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%"))
                        ->orWhereHas('variant.product', fn (Builder $productQuery) => $productQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            });
    }
}
