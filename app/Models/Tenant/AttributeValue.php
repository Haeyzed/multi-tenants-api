<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Predefined value for a product attribute.
 *
 * @property int $id
 * @property int $attribute_id
 * @property string $value
 * @property string $slug
 * @property string|null $color_hex
 * @property int|null $image_media_id
 * @property string|null $description
 * @property bool $is_default
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Attribute $attribute
 * @property-read Media|null $image
 * @property-read EloquentCollection<int, Product> $products
 * @property-read EloquentCollection<int, ProductVariant> $variants
 *
 * @method static Builder<AttributeValue>|AttributeValue query()
 */
class AttributeValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'color_hex',
        'image_media_id',
        'description',
        'is_default',
        'sort_order',
    ];

    /**
     * Parent attribute definition.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Optional swatch or preview image.
     *
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Products using this attribute value.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot(['attribute_id', 'custom_value', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Variants using this attribute value.
     *
     * @return BelongsToMany<ProductVariant, $this>
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_attribute_values')
            ->withPivot(['attribute_id'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
