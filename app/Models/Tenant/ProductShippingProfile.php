<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Shipping override profile for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $profile_name
 * @property string $additional_cost
 * @property bool $is_free_shipping
 * @property int $processing_days
 * @property array<string, mixed>|null $excluded_regions
 * @property array<string, mixed>|null $included_regions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static Builder<ProductShippingProfile>|ProductShippingProfile query()
 */
class ProductShippingProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'profile_name',
        'additional_cost',
        'is_free_shipping',
        'processing_days',
        'excluded_regions',
        'included_regions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'additional_cost' => 'decimal:2',
            'is_free_shipping' => 'boolean',
            'processing_days' => 'integer',
            'excluded_regions' => 'array',
            'included_regions' => 'array',
        ];
    }

    /**
     * Product this shipping profile belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
