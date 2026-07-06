<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Volume or bulk pricing tier for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_variant_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property string $price
 * @property string|null $customer_group_id
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 *
 * @method static Builder<ProductPriceTier>|ProductPriceTier query()
 */
class ProductPriceTier extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'min_quantity',
        'max_quantity',
        'price',
        'customer_group_id',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'price' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /**
     * Variant this tier applies to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Scope a query to currently active tiers.
     *
     * @param  Builder<ProductPriceTier>  $query
     * @return Builder<ProductPriceTier>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope a query to tiers matching a quantity.
     *
     * @param  Builder<ProductPriceTier>  $query
     * @return Builder<ProductPriceTier>
     */
    public function scopeForQuantity(Builder $query, int $quantity): Builder
    {
        return $query
            ->where('min_quantity', '<=', $quantity)
            ->where(function (Builder $q) use ($quantity): void {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            });
    }
}
