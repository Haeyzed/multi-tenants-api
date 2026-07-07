<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\ProductCondition;
use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use Database\Factories\Tenant\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Core catalog product entity.
 *
 * Products are catalog records only. SKU, pricing, barcode, and inventory
 * live exclusively on product variants.
 *
 * @property int $id
 * @property int|null $brand_id
 * @property int|null $attribute_set_id
 * @property int|null $tax_class_id
 * @property string $name
 * @property string $slug
 * @property string|null $subtitle
 * @property string|null $description
 * @property string|null $summary
 * @property ProductType $type
 * @property ProductCondition $condition
 * @property ProductStatus $status
 * @property ProductVisibility $visibility
 * @property bool $is_featured
 * @property bool $is_returnable
 * @property int|null $return_period_days
 * @property int|null $warranty_period_months
 * @property int $min_order_quantity
 * @property int|null $max_order_quantity
 * @property bool $track_inventory
 * @property bool $allow_backorders
 * @property bool $requires_shipping
 * @property bool $is_taxable
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $search_keywords
 * @property Carbon|null $published_at
 * @property Carbon|null $discontinued_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $admin_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Brand|null $brand
 * @property-read AttributeSet|null $attributeSet
 * @property-read TaxClass|null $taxClass
 * @property-read EloquentCollection<int, Category> $categories
 * @property-read EloquentCollection<int, Tag> $tags
 * @property-read EloquentCollection<int, ProductSupplier> $productSuppliers
 * @property-read EloquentCollection<int, Supplier> $suppliers
 * @property-read EloquentCollection<int, ProductOption> $options
 * @property-read EloquentCollection<int, ProductVariant> $variants
 * @property-read EloquentCollection<int, ProductVariant> $activeVariants
 * @property-read ProductVariant|null $defaultVariant
 * @property-read EloquentCollection<int, ProductImage> $productImages
 * @property-read EloquentCollection<int, ProductImage> $images
 * @property-read EloquentCollection<int, Collection> $collections
 * @property-read EloquentCollection<int, ProductAttributeValue> $attributeValues
 * @property-read EloquentCollection<int, ProductReview> $reviews
 * @property-read EloquentCollection<int, ProductFaq> $faqs
 * @property-read EloquentCollection<int, ProductDocument> $documents
 * @property-read EloquentCollection<int, ProductQuestion> $questions
 * @property-read ProductSeo|null $seo
 * @property-read EloquentCollection<int, ProductVideo> $videos
 * @property-read EloquentCollection<int, ProductRelatedProduct> $relatedProducts
 * @property-read EloquentCollection<int, ProductRelatedProduct> $crossSellProducts
 * @property-read EloquentCollection<int, ProductRelatedProduct> $upSellProducts
 * @property-read ProductService|null $service
 * @property-read ProductSubscription|null $subscription
 * @property-read TenantUser|null $creator
 * @property-read TenantUser|null $updater
 * @property-read TenantUser|null $approver
 *
 * @method static Builder<Product>|Product query()
 * @method static Builder<Product>|Product filter(array $filters)
 * @method static Builder<Product>|Product search(string $search)
 * @method static Builder<Product>|Product visible()
 * @method static Builder<Product>|Product featured()
 * @method static Builder<Product>|Product ofType(ProductType|string $type)
 * @method static Builder<Product>|Product status(ProductStatus|string $status)
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_id',
        'attribute_set_id',
        'tax_class_id',
        'name',
        'slug',
        'subtitle',
        'description',
        'summary',
        'type',
        'condition',
        'status',
        'visibility',
        'is_featured',
        'is_returnable',
        'return_period_days',
        'warranty_period_months',
        'min_order_quantity',
        'max_order_quantity',
        'track_inventory',
        'allow_backorders',
        'requires_shipping',
        'is_taxable',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'search_keywords',
        'published_at',
        'discontinued_at',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'type',
                'status',
                'visibility',
                'brand_id',
                'attribute_set_id',
                'tax_class_id',
                'is_featured',
                'published_at',
            ])
            ->logOnlyDirty();
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
     * Get the brand associated with the product.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the attribute set assigned to the product.
     *
     * @return BelongsTo<AttributeSet, $this>
     */
    public function attributeSet(): BelongsTo
    {
        return $this->belongsTo(AttributeSet::class);
    }

    /**
     * Get the default tax class for the product.
     *
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    /**
     * Get the primary category from the pivot table.
     */
    public function primaryCategory(): ?Category
    {
        return $this->categories()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get all categories assigned to the product.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get the tags associated with the product.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<ProductLabel, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ProductLabel::class, 'product_product_label')
            ->withPivot(['starts_at', 'ends_at', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Get supplier sourcing records for the product.
     *
     * @return HasMany<ProductSupplier, $this>
     */
    public function productSuppliers(): HasMany
    {
        return $this->hasMany(ProductSupplier::class);
    }

    /**
     * Get the primary supplier for procurement.
     */
    public function primarySupplier(): ?Supplier
    {
        return $this->suppliers()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get suppliers linked to the product with commercial terms.
     *
     * @return BelongsToMany<Supplier, $this>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_suppliers')
            ->withPivot([
                'supplier_sku',
                'supplier_cost',
                'lead_time_days',
                'minimum_quantity',
                'is_primary',
            ])
            ->withTimestamps();
    }

    /**
     * Get variant-generating options for the product.
     *
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * Get active variants for the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->active();
    }

    /**
     * Get all variants for the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * Get the default variant for the product.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Alias for product gallery images.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->productImages();
    }

    /**
     * Get ordered gallery images for the product.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get collections this product belongs to.
     *
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get attribute values assigned to the product.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get customer reviews for the product.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get FAQs for the product.
     *
     * @return HasMany<ProductFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    /**
     * Get documents attached to the product.
     *
     * @return HasMany<ProductDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order');
    }

    /**
     * Get customer questions for the product.
     *
     * @return HasMany<ProductQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class)->latest();
    }

    /**
     * Get extended SEO data for the product.
     *
     * @return HasOne<ProductSeo, $this>
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ProductSeo::class);
    }

    /**
     * Get videos attached to the product.
     *
     * @return HasMany<ProductVideo, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    /**
     * Get related product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(ProductRelatedProduct::class)
            ->where('relation_type', 'related')
            ->orderBy('sort_order');
    }

    /**
     * Get cross-sell product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function crossSellProducts(): HasMany
    {
        return $this->hasMany(ProductRelatedProduct::class)
            ->where('relation_type', 'cross_sell')
            ->orderBy('sort_order');
    }

    /**
     * Get upsell product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function upSellProducts(): HasMany
    {
        return $this->hasMany(ProductRelatedProduct::class)
            ->where('relation_type', 'upsell')
            ->orderBy('sort_order');
    }

    /**
     * Get downloadable files for digital products.
     *
     * @return HasMany<ProductDownload, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class)->orderBy('sort_order');
    }

    /**
     * Get bundle component items.
     *
     * @return HasMany<ProductBundle, $this>
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundle::class)->orderBy('sort_order');
    }

    /**
     * Get service providers assigned to the product.
     *
     * @return HasMany<ProductProvider, $this>
     */
    public function providers(): HasMany
    {
        return $this->hasMany(ProductProvider::class);
    }

    /**
     * Service configuration for service-type products.
     *
     * @return HasOne<ProductService, $this>
     */
    public function service(): HasOne
    {
        return $this->hasOne(ProductService::class);
    }

    /**
     * Subscription configuration for subscription-type products.
     *
     * @return HasOne<ProductSubscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(ProductSubscription::class);
    }

    /**
     * Get the user who created the product.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    /**
     * Get the user who last updated the product.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }

    /**
     * Get the user who approved the product.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'approved_by');
    }

    /**
     * Determine if the product requires shipping based on type and flag.
     */
    public function requiresShipping(): bool
    {
        if (!$this->requires_shipping) {
            return false;
        }

        return $this->type->requiresShipping();
    }

    /**
     * Determine if inventory should be tracked for this product.
     */
    public function tracksInventory(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->type->tracksInventory();
    }

    /**
     * Determine if the product is published and publicly visible.
     */
    public function isPublished(): bool
    {
        if ($this->status !== ProductStatus::Active) {
            return false;
        }

        if (!$this->visibility->isPubliclyVisible()) {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        if ($this->discontinued_at !== null && $this->discontinued_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope a query to search products by catalog fields.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search): void {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('subtitle', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('meta_keywords', 'like', "%{$search}%")
                ->orWhere('search_keywords', 'like', "%{$search}%")
                ->orWhereHas('variants', function (Builder $variantQuery) use ($search): void {
                    $variantQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Scope a query to filter products.
     *
     * @param Builder<Product> $query
     * @param array<string, mixed> $filters
     * @return Builder<Product>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->search((string)$filters['search']);
            })
            ->when(!empty($filters['brand_id']), function (Builder $q) use ($filters): void {
                $brandIds = is_array($filters['brand_id'])
                    ? $filters['brand_id']
                    : explode(',', (string)$filters['brand_id']);

                $q->whereIn('brand_id', $brandIds);
            })
            ->when(!empty($filters['status']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['status'])
                    ? $filters['status']
                    : explode(',', (string)$filters['status']);

                $q->whereIn('status', $statuses);
            })
            ->when(!empty($filters['visibility']), function (Builder $q) use ($filters): void {
                $values = is_array($filters['visibility'])
                    ? $filters['visibility']
                    : explode(',', (string)$filters['visibility']);

                $q->whereIn('visibility', $values);
            })
            ->when(!empty($filters['is_featured']), function (Builder $q) use ($filters): void {
                $values = is_array($filters['is_featured'])
                    ? $filters['is_featured']
                    : explode(',', (string)$filters['is_featured']);

                $booleans = [];

                if (in_array('featured', $values, true) || in_array('1', $values, true) || in_array(true, $values, true)) {
                    $booleans[] = true;
                }

                if (in_array('not_featured', $values, true) || in_array('0', $values, true) || in_array(false, $values, true)) {
                    $booleans[] = false;
                }

                if ($booleans !== []) {
                    $q->whereIn('is_featured', $booleans);
                }
            })
            ->when(!empty($filters['category_id']) || !empty($filters['category_ids']), function (Builder $q) use ($filters): void {
                $categoryIds = !empty($filters['category_ids'])
                    ? (is_array($filters['category_ids']) ? $filters['category_ids'] : explode(',', (string)$filters['category_ids']))
                    : (is_array($filters['category_id']) ? $filters['category_id'] : [$filters['category_id']]);

                $q->whereHas('categories', function (Builder $categoryQuery) use ($categoryIds): void {
                    $categoryQuery->whereIn('categories.id', $categoryIds);
                });
            })
            ->when(!empty($filters['primary_category_id']), function (Builder $q) use ($filters): void {
                $categoryId = $filters['primary_category_id'];

                $q->whereHas('categories', function (Builder $categoryQuery) use ($categoryId): void {
                    $categoryQuery->where('categories.id', $categoryId)
                        ->where('category_product.is_primary', true);
                });
            })
            ->when(!empty($filters['type']), function (Builder $q) use ($filters): void {
                $types = is_array($filters['type']) ? $filters['type'] : explode(',', (string)$filters['type']);
                $q->whereIn('type', $types);
            })
            ->when(!empty($filters['condition']), function (Builder $q) use ($filters): void {
                $conditions = is_array($filters['condition'])
                    ? $filters['condition']
                    : explode(',', (string)$filters['condition']);

                $q->whereIn('condition', $conditions);
            })
            ->when(!empty($filters['tag_ids']), function (Builder $q) use ($filters): void {
                $tagIds = is_array($filters['tag_ids'])
                    ? $filters['tag_ids']
                    : explode(',', (string)$filters['tag_ids']);

                $q->whereHas('tags', function (Builder $tagQuery) use ($tagIds): void {
                    $tagQuery->whereIn('tags.id', $tagIds);
                });
            })
            ->when(!empty($filters['supplier_id']), function (Builder $q) use ($filters): void {
                $supplierIds = is_array($filters['supplier_id'])
                    ? $filters['supplier_id']
                    : explode(',', (string)$filters['supplier_id']);

                $q->whereHas('suppliers', function (Builder $supplierQuery) use ($supplierIds): void {
                    $supplierQuery->whereIn('suppliers.id', $supplierIds);
                });
            })
            ->when(!empty($filters['min_price']) || !empty($filters['max_price']), function (Builder $q) use ($filters): void {
                $q->whereHas('variants', function (Builder $variantQuery) use ($filters): void {
                    if (!empty($filters['min_price'])) {
                        $variantQuery->where('price', '>=', (float)$filters['min_price']);
                    }

                    if (!empty($filters['max_price'])) {
                        $variantQuery->where('price', '<=', (float)$filters['max_price']);
                    }
                });
            })
            ->when(!empty($filters['has_variants']), function (Builder $q): void {
                $q->has('variants', '>', 1);
            })
            ->when(isset($filters['track_inventory']), function (Builder $q) use ($filters): void {
                $q->where('track_inventory', filter_var($filters['track_inventory'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(!empty($filters['created_from']), function (Builder $q) use ($filters): void {
                $q->whereDate('created_at', '>=', $filters['created_from']);
            })
            ->when(!empty($filters['created_to']), function (Builder $q) use ($filters): void {
                $q->whereDate('created_at', '<=', $filters['created_to']);
            })
            ->when(isset($filters['in_stock']), function (Builder $q) use ($filters): void {
                $inStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN);

                $q->whereHas('variants.inventories', function (Builder $inventoryQuery) use ($inStock): void {
                    if ($inStock) {
                        $inventoryQuery->whereColumn('quantity', '>', 'reserved_quantity');
                    } else {
                        $inventoryQuery->whereColumn('quantity', '<=', 'reserved_quantity');
                    }
                });
            });
    }

    /**
     * Scope a query to only include publicly visible products.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Active)
            ->whereIn('visibility', [
                ProductVisibility::Visible,
                ProductVisibility::Catalog,
                ProductVisibility::Search,
            ])
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('discontinued_at')
                    ->orWhere('discontinued_at', '>', now());
            });
    }

    /**
     * Scope a query to filter by product status.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeStatus(Builder $query, ProductStatus|string $status): Builder
    {
        $value = $status instanceof ProductStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * Scope a query to only include featured products.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include products of a specific type.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeOfType(Builder $query, ProductType|string $type): Builder
    {
        $value = $type instanceof ProductType ? $type->value : $type;

        return $query->where('type', $value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'condition' => ProductCondition::class,
            'status' => ProductStatus::class,
            'visibility' => ProductVisibility::class,
            'is_featured' => 'boolean',
            'is_returnable' => 'boolean',
            'return_period_days' => 'integer',
            'warranty_period_months' => 'integer',
            'min_order_quantity' => 'integer',
            'max_order_quantity' => 'integer',
            'track_inventory' => 'boolean',
            'allow_backorders' => 'boolean',
            'requires_shipping' => 'boolean',
            'is_taxable' => 'boolean',
            'published_at' => 'datetime',
            'discontinued_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
