<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\AttributeSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Group of attributes assigned to categories or products.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EloquentCollection<int, Attribute> $attributes
 * @property-read EloquentCollection<int, Category> $categories
 *
 * @method static Builder<AttributeSet>|AttributeSet query()
 * @method static Builder<AttributeSet>|AttributeSet filter(array $filters)
 */
class AttributeSet extends Model
{
    /** @use HasFactory<AttributeSetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Attributes belonging to this set.
     *
     * @return BelongsToMany<Attribute, $this>
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_set_attributes')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps()
            ->orderBy('attribute_set_attributes.sort_order');
    }

    /**
     * Categories using this attribute set.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attribute_sets')
            ->withTimestamps();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttributeSetFactory
    {
        return AttributeSetFactory::new();
    }

    /**
     * @param  Builder<AttributeSet>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<AttributeSet>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%'.$filters['search'].'%');
            })
            ->when(isset($filters['is_active']), function (Builder $q) use ($filters): void {
                $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            });
    }
}
