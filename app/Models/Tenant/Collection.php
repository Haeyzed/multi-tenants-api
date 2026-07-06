<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\CollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @method static Builder<Collection>|Collection filter(array $filters)
 */
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, SoftDeletes;

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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionFactory
    {
        return CollectionFactory::new();
    }

    /**
     * @param  Builder<Collection>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Collection>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%'.$filters['search'].'%');
            })
            ->when(! empty($filters['is_visible']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_visible'])
                    ? $filters['is_visible']
                    : explode(',', (string) $filters['is_visible']);

                $booleans = [];

                if (in_array('visible', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('hidden', $statuses, true)) {
                    $booleans[] = false;
                }

                if (! empty($booleans)) {
                    $q->whereIn('is_visible', $booleans);
                }
            })
            ->when(isset($filters['is_featured']), function (Builder $q) use ($filters): void {
                $q->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(! empty($filters['type']), function (Builder $q) use ($filters): void {
                $q->where('type', $filters['type']);
            });
    }
}
