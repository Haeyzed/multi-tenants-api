<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Product category for organizing catalog items.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $summary
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $parent_id
 * @property int $depth
 * @property string|null $path
 * @property bool $is_visible
 * @property bool $is_featured
 * @property int $sort_order
 * @property int|null $image_media_id
 * @property string|null $banner_media_id
 * @property int|null $icon_media_id
 * @property string|null $color
 * @property string|null $icon_class
 * @property string|null $layout_template
 * @property int $products_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Product> $primaryProducts
 * @property-read Collection<int, AttributeSet> $attributeSets
 * @property-read Media|null $imageMedia
 * @property-read Media|null $bannerMedia
 * @property-read Media|null $iconMedia
 *
 * @method static Builder<Category>|Category query()
 * @method static Builder<Category>|Category filter(array $filters)
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'summary',
        'meta_title',
        'meta_description',
        'parent_id',
        'depth',
        'path',
        'is_visible',
        'is_featured',
        'sort_order',
        'image_media_id',
        'banner_media_id',
        'icon_media_id',
        'color',
        'icon_class',
        'layout_template',
        'products_count',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'products_count' => 'integer',
        ];
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the products in this category via pivot.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Get products for which this is the primary category.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function primaryProducts(): BelongsToMany
    {
        return $this->products()->wherePivot('is_primary', true);
    }

    /**
     * Get attribute sets linked to this category.
     *
     * @return BelongsToMany<AttributeSet, $this>
     */
    public function attributeSets(): BelongsToMany
    {
        return $this->belongsToMany(AttributeSet::class, 'category_attribute_sets')
            ->withTimestamps();
    }

    /**
     * Image media file for this category.
     *
     * @return BelongsTo<Media, $this>
     */
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->imageMedia();
    }

    /**
     * Banner media file for this category.
     *
     * @return BelongsTo<Media, $this>
     */
    public function bannerMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->bannerMedia();
    }

    /**
     * Icon media file for this category.
     *
     * @return BelongsTo<Media, $this>
     */
    public function iconMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function icon(): BelongsTo
    {
        return $this->iconMedia();
    }

    /**
     * Scope a query to filter categories.
     *
     * @param  Builder<Category>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Category>
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
            ->when(! empty($filters['parent_id']), function (Builder $q) use ($filters): void {
                $q->where('parent_id', $filters['parent_id']);
            })
            ->when(isset($filters['is_featured']), function (Builder $q) use ($filters): void {
                $q->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(isset($filters['has_products']), function (Builder $q): void {
                $q->has('products');
            });
    }

    /**
     * Get all ancestor categories.
     *
     * @return Collection<int, Category>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get full breadcrumb path as array.
     *
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function breadcrumbPath(): array
    {
        $path = [];
        foreach ($this->ancestors() as $ancestor) {
            $path[] = ['id' => $ancestor->id, 'name' => $ancestor->name, 'slug' => $ancestor->slug];
        }
        $path[] = ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug];

        return $path;
    }
}
