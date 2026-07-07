<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\ProductLabelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Reusable storefront label for products (New, Sale, etc.).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property string|null $background_color
 * @property string|null $icon
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProductLabel extends Model
{
    /** @use HasFactory<ProductLabelFactory> */
    use HasFactory, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'background_color',
        'icon',
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

    protected static function newFactory(): ProductLabelFactory
    {
        return ProductLabelFactory::new();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_label')
            ->withPivot(['starts_at', 'ends_at', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * @param  Builder<ProductLabel>  $query
     * @return Builder<ProductLabel>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * @param  Builder<ProductLabel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<ProductLabel>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->where('name', 'like', '%'.$filters['search'].'%');
            })
            ->when(! empty($filters['is_active']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_active'])
                    ? $filters['is_active']
                    : explode(',', (string) $filters['is_active']);

                $booleans = [];

                if (in_array('active', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('inactive', $statuses, true)) {
                    $booleans[] = false;
                }

                if (! empty($booleans)) {
                    $q->whereIn('is_active', $booleans);
                }
            });
    }
}
