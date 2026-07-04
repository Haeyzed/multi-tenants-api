<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Attribute value assigned to a product variant.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $attribute_id
 * @property int $attribute_value_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductVariant $variant
 * @property-read Attribute $attribute
 * @property-read AttributeValue $attributeValue
 *
 * @method static Builder<VariantAttributeValue>|VariantAttributeValue query()
 */
class VariantAttributeValue extends Model
{
    protected $table = 'variant_attribute_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'attribute_id',
        'attribute_value_id',
    ];

    /**
     * Variant this value belongs to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Attribute definition for this value.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Predefined attribute value.
     *
     * @return BelongsTo<AttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
