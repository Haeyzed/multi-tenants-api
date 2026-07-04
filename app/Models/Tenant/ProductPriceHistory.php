<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit record for a product price change.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $variant_id
 * @property string $price_type
 * @property string $old_price
 * @property string $new_price
 * @property int|null $changed_by
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 * @property-read TenantUser|null $changer
 *
 * @method static Builder<ProductPriceHistory>|ProductPriceHistory query()
 */
class ProductPriceHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'price_type',
        'old_price',
        'new_price',
        'changed_by',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
        ];
    }

    /**
     * Product whose price changed.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant whose price changed, if applicable.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Staff user who changed the price.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'changed_by');
    }
}
