<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Links a variant to its option value combination.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $product_option_id
 * @property int $product_option_value_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductVariant $variant
 * @property-read ProductOption $option
 * @property-read ProductOptionValue $optionValue
 *
 * @method static Builder<VariantOptionValue>|VariantOptionValue query()
 */
class VariantOptionValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'product_option_id',
        'product_option_value_id',
    ];

    /**
     * Get the variant for this option selection.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the option dimension for this selection.
     *
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    /**
     * Get the selected option value.
     *
     * @return BelongsTo<ProductOptionValue, $this>
     */
    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
