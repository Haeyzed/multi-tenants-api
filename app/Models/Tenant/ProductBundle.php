<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Included product within a bundle offering.
 *
 * @property int $id
 * @property int $product_id
 * @property int $included_product_id
 * @property int|null $included_variant_id
 * @property int $quantity
 * @property bool $is_optional
 * @property string|null $discount_percentage
 * @property string|null $fixed_price
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Product $includedProduct
 * @property-read ProductVariant|null $includedVariant
 * @property-read float $effective_price
 *
 * @method static Builder<ProductBundle>|ProductBundle query()
 */
class ProductBundle extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'included_product_id',
        'included_variant_id',
        'quantity',
        'is_optional',
        'discount_percentage',
        'fixed_price',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_optional' => 'boolean',
            'discount_percentage' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Bundle product that owns this item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product included in the bundle.
     *
     * @return BelongsTo<Product, $this>
     */
    public function includedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'included_product_id');
    }

    /**
     * Specific variant included in the bundle.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function includedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'included_variant_id');
    }

    /**
     * Effective price after bundle adjustments.
     */
    public function getEffectivePriceAttribute(): float
    {
        $basePrice = $this->included_variant_id
            ? ($this->includedVariant?->price ?? 0)
            : ($this->includedProduct?->price ?? 0);

        if ($this->fixed_price !== null) {
            return (float) $this->fixed_price;
        }

        if ($this->discount_percentage) {
            return (float) $basePrice * (1 - ((float) $this->discount_percentage / 100));
        }

        return (float) $basePrice;
    }
}
