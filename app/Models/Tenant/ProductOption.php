<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Variant-generating product option (Color, Size, etc.).
 *
 * Distinct from catalog attributes used for specifications and filtering.
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $code
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read EloquentCollection<int, ProductOptionValue> $values
 *
 * @method static Builder<ProductOption>|ProductOption query()
 */
class ProductOption extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'code',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Get the product that owns this option.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get values available for this option.
     *
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('position');
    }
}
