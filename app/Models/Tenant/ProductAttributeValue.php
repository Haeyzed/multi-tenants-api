<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Attribute value assigned to a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $attribute_id
 * @property int|null $attribute_value_id
 * @property string|null $custom_value
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Attribute $attribute
 * @property-read AttributeValue|null $attributeValue
 * @property-read string $display_value
 *
 * @method static Builder<ProductAttributeValue>|ProductAttributeValue query()
 */
class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id',
        'custom_value',
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
     * Product this value belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
     * Predefined attribute value, if used.
     *
     * @return BelongsTo<AttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    /**
     * Human-readable value for display.
     */
    public function getDisplayValueAttribute(): string
    {
        return $this->attribute_value_id
            ? $this->attributeValue->value
            : (string) $this->custom_value;
    }
}
