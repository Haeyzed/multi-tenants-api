<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Curated product collection within a tenant store.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $image_media_id
 * @property int|null $banner_media_id
 * @property bool $is_visible
 * @property bool $is_featured
 * @property int $sort_order
 * @property string $type
 * @property array<string, mixed>|null $conditions
 * @property string $sort_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EloquentCollection<int, Product> $products
 * @property-read Media|null $image
 * @property-read Media|null $banner
 *
 * @method static Builder<Collection>|Collection query()
 */
class Collection extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'image_media_id',
        'banner_media_id',
        'is_visible',
        'is_featured',
        'sort_order',
        'type',
        'conditions',
        'sort_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'conditions' => 'array',
        ];
    }

    /**
     * Products in this collection.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderBy('collection_products.sort_order');
    }

    /**
     * Cover image for this collection.
     *
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Banner image for this collection.
     *
     * @return BelongsTo<Media, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_media_id');
    }

    /**
     * Scope a query to only visible collections.
     *
     * @param  Builder<Collection>  $query
     * @return Builder<Collection>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Determine if the collection is manually curated.
     */
    public function getIsManualAttribute(): bool
    {
        return $this->type === 'manual';
    }

    /**
     * Determine if the collection is rule-based.
     */
    public function getIsAutomatedAttribute(): bool
    {
        return $this->type === 'automated';
    }
}
