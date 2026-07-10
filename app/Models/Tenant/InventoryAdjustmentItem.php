<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\InventoryAdjustmentItemAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_adjustment_id
 * @property int $product_id
 * @property int $product_variant_id
 * @property int|null $inventory_id
 * @property InventoryAdjustmentItemAction $action
 * @property int $quantity
 * @property int $quantity_change
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string|null $unit_cost
 * @property int $sort_order
 * @property-read InventoryAdjustment $adjustment
 * @property-read Product $product
 * @property-read ProductVariant $variant
 * @property-read Inventory|null $inventory
 */
class InventoryAdjustmentItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_adjustment_id',
        'product_id',
        'product_variant_id',
        'inventory_id',
        'action',
        'quantity',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => InventoryAdjustmentItemAction::class,
            'quantity' => 'integer',
            'quantity_change' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'unit_cost' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InventoryAdjustment, $this>
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
