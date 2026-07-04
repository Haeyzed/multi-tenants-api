<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Structured specification for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $group
 * @property string $key
 * @property string $value
 * @property string|null $unit
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static Builder<ProductSpecification>|ProductSpecification query()
 */
class ProductSpecification extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'group',
        'key',
        'value',
        'unit',
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
     * Product this specification belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
