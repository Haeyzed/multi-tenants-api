<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Related product link such as upsell or cross-sell.
 *
 * @property int $id
 * @property int $product_id
 * @property int $related_product_id
 * @property string $relation_type
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Product $relatedProduct
 *
 * @method static Builder<ProductRelatedProduct>|ProductRelatedProduct query()
 */
class ProductRelatedProduct extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'related_product_id',
        'relation_type',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Source product for the relation.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Related target product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
