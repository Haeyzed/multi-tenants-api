<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Product attribute definition within a tenant catalog.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $code
 * @property string $type
 * @property string $display_type
 * @property string|null $description
 * @property bool $is_filterable
 * @property bool $is_visible_on_product
 * @property bool $is_visible_on_listing
 * @property bool $is_required
 * @property bool $is_variant
 * @property bool $is_user_defined
 * @property int $sort_order
 * @property array<string, mixed>|null $validation_rules
 * @property array<string, mixed>|null $default_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EloquentCollection<int, AttributeValue> $values
 * @property-read EloquentCollection<int, AttributeSet> $attributeSets
 * @property-read EloquentCollection<int, Product> $products
 *
 * @method static Builder<Attribute>|Attribute query()
 */
class Attribute extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'type',
        'display_type',
        'description',
        'is_filterable',
        'is_visible_on_product',
        'is_visible_on_listing',
        'is_required',
        'is_variant',
        'is_user_defined',
        'sort_order',
        'validation_rules',
        'default_value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_visible_on_product' => 'boolean',
            'is_visible_on_listing' => 'boolean',
            'is_required' => 'boolean',
            'is_variant' => 'boolean',
            'is_user_defined' => 'boolean',
            'sort_order' => 'integer',
            'validation_rules' => 'array',
            'default_value' => 'array',
        ];
    }

    /**
     * Predefined values for this attribute.
     *
     * @return HasMany<AttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Attribute sets that include this attribute.
     *
     * @return BelongsToMany<AttributeSet, $this>
     */
    public function attributeSets(): BelongsToMany
    {
        return $this->belongsToMany(AttributeSet::class, 'attribute_set_attributes')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Products linked to this attribute.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot(['attribute_value_id', 'custom_value', 'sort_order'])
            ->withTimestamps();
    }
}
