<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Stock level for a product or variant.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $warehouse_id
 * @property int $quantity
 * @property int $reserved_quantity
 * @property int $available_quantity
 * @property int $low_stock_threshold
 * @property string|null $location_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 * @property-read Warehouse|null $warehouse
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
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'location_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'reserved_quantity', 'low_stock_threshold', 'warehouse_id', 'location_code'])
            ->logOnlyDirty();
    }

    /**
     * Get the product associated with this inventory.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specific variant associated with this inventory.
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
     * Determine if the stock is below or equal to the threshold.
     */
    public function isLowStock(): bool
    {
        return $this->availableQuantity() <= $this->low_stock_threshold;
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
    public function adjust(int $change, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $reason = null): InventoryMovement
    {
        $quantityBefore = $this->quantity;
        $this->quantity += $change;
        $this->save();

        return $this->movements()->create([
            'quantity_change' => $change,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
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
}
