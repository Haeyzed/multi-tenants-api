<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Value for a product option used to generate variants.
 *
 * @property int $id
 * @property int $product_option_id
 * @property string $value
 * @property string $code
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductOption $option
 * @property-read EloquentCollection<int, VariantOptionValue> $variantOptionValues
 * @property-read EloquentCollection<int, ProductVariant> $variants
 *
 * @method static Builder<ProductOptionValue>|ProductOptionValue query()
 */
class ProductOptionValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_option_id',
        'value',
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
     * Get the option that owns this value.
     *
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    /**
     * Get pivot records linking this value to variants.
     *
     * @return HasMany<VariantOptionValue, $this>
     */
    public function variantOptionValues(): HasMany
    {
        return $this->hasMany(VariantOptionValue::class);
    }

    /**
     * Get variants that use this option value.
     *
     * @return BelongsToMany<ProductVariant, $this>
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_option_values',
            'product_option_value_id',
            'product_variant_id'
        )
            ->withPivot(['product_option_id'])
            ->withTimestamps();
    }
}
