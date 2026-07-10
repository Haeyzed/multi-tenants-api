<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inventory_transfer_id
 * @property int $product_id
 * @property int $product_variant_id
 * @property int|null $inventory_id
 * @property int $quantity
 * @property string $unit_cost
 * @property string $tax_rate
 * @property string $tax_amount
 * @property string $subtotal
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InventoryTransfer $transfer
 * @property-read Product $product
 * @property-read ProductVariant $variant
 * @property-read Inventory|null $inventory
 */
class InventoryTransferItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_transfer_id',
        'product_id',
        'product_variant_id',
        'inventory_id',
        'quantity',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InventoryTransfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
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
