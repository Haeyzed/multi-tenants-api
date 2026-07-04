<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
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
 * Sellable product in a tenant flash-sale store.
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property string $sku
 * @property float $price
 * @property float|null $compare_at_price
 * @property float|null $cost_price
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property bool $is_visible
 * @property bool $is_featured
 * @property string $product_type
 * @property int|null $download_limit
 * @property int|null $download_expiry_days
 * @property int|null $preview_media_id
 * @property int|null $duration_minutes
 * @property int|null $buffer_minutes
 * @property int|null $max_participants
 * @property string|null $location_type
 * @property string|null $service_location
 * @property bool $allow_partial_combo
 * @property string|null $youtube_url
 * @property string|null $tax_class_id
 * @property float $weight
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property string|null $weight_unit
 * @property string|null $dimension_unit
 * @property string|null $barcode
 * @property string|null $mpn
 * @property string|null $gtin
 * @property int|null $primary_image_media_id
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read EloquentCollection<int, Category> $categories
 * @property-read Brand|null $brand
 * @property-read AttributeSet|null $attributeSet
 * @property-read EloquentCollection<int, Tag> $tags
 * @property-read EloquentCollection<int, ProductVariant> $variants
 * @property-read EloquentCollection<int, ProductVariant> $activeVariants
 * @property-read ProductVariant|null $defaultVariant
 * @property-read EloquentCollection<int, ProductAttributeValue> $attributeValues
 * @property-read EloquentCollection<int, Inventory> $inventories
 * @property-read EloquentCollection<int, ProductImage> $images
 * @property-read ProductImage|null $primaryImage
 * @property-read Media|null $primaryImageMedia
 * @property-read EloquentCollection<int, ProductVideo> $videos
 * @property-read ProductVideo|null $primaryVideo
 * @property-read EloquentCollection<int, ProductDownload> $downloads
 * @property-read EloquentCollection<int, ProductDownload> $previewDownloads
 * @property-read ProductService|null $service
 * @property-read EloquentCollection<int, ProductBundle> $bundleItems
 * @property-read EloquentCollection<int, ProductBundle> $includedInBundles
 * @property-read ProductSubscription|null $subscription
 * @property-read EloquentCollection<int, ProductReview> $reviews
 * @property-read EloquentCollection<int, ProductReview> $approvedReviews
 * @property-read EloquentCollection<int, ProductRelatedProduct> $relatedProducts
 * @property-read EloquentCollection<int, ProductRelatedProduct> $upsells
 * @property-read EloquentCollection<int, ProductRelatedProduct> $crossSells
 * @property-read EloquentCollection<int, ProductRelatedProduct> $accessories
 * @property-read EloquentCollection<int, Collection> $collections
 * @property-read ProductSeo|null $seo
 * @property-read EloquentCollection<int, ProductPriceTier> $priceTiers
 * @property-read EloquentCollection<int, ProductPriceHistory> $priceHistories
 * @property-read EloquentCollection<int, ProductSpecification> $specifications
 * @property-read EloquentCollection<int, ProductDocument> $documents
 * @property-read EloquentCollection<int, ProductDocument> $publicDocuments
 * @property-read EloquentCollection<int, ProductFaq> $faqs
 * @property-read EloquentCollection<int, ProductFaq> $visibleFaqs
 * @property-read EloquentCollection<int, ProductShippingProfile> $shippingProfiles
 * @property-read TaxClass|null $taxClass
 * @property-read Supplier|null $supplier
 * @property-read Unit|null $weightUnit
 * @property-read Unit|null $dimensionUnit
 * @property-read TenantUser|null $creator
 * @property-read TenantUser|null $updater
 * @property-read TenantUser|null $approver
 * @property-read float|null $average_rating
 * @property-read int $review_count
 *
 * @method static Builder<Product>|Product query()
 * @method static Builder<Product>|Product filter(array $filters)
 * @method static Builder<Product>|Product search(string $search)
 * @method static Builder<Product>|Product visible()
 * @method static Builder<Product>|Product featured()
 * @method static Builder<Product>|Product inStock()
 * @method static Builder<Product>|Product lowStock()
 * @method static Builder<Product>|Product ofType(string $type)
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'attribute_set_id',
        'name',
        'slug',
        'sku',
        'subtitle',
        'description',
        'summary',
        'condition',
        'price',
        'compare_at_price',
        'cost_price',
        'track_inventory',
        'quantity',
        'low_stock_threshold',
        'allow_backorders',
        'restock_date',
        'lead_time_days',
        'weight',
        'length',
        'width',
        'height',
        'weight_unit_id',
        'dimension_unit_id',
        'barcode',
        'mpn',
        'gtin',
        'hs_code',
        'country_of_origin',
        'tax_class_id',
        'primary_image_media_id',
        'product_type',
        'status',
        'is_visible',
        'is_featured',
        'is_returnable',
        'return_period_days',
        'warranty_period_months',
        'min_order_quantity',
        'max_order_quantity',
        'supplier_id',
        'supplier_sku',
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'status' => ProductStatus::class,
            'track_inventory' => 'boolean',
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'allow_backorders' => 'boolean',
            'restock_date' => 'datetime',
            'lead_time_days' => 'integer',
            'product_type' => ProductType::class,
            'weight' => 'decimal:3',
            'length' => 'decimal:3',
            'width' => 'decimal:3',
            'height' => 'decimal:3',
            'is_returnable' => 'boolean',
            'return_period_days' => 'integer',
            'warranty_period_months' => 'integer',
            'min_order_quantity' => 'integer',
            'max_order_quantity' => 'integer',
            'published_at' => 'datetime',
            'discontinued_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'price', 'status', 'is_visible', 'is_featured', 'category_id', 'brand_id', 'product_type'])
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
     * Get the primary category that the product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
            ->withTimestamps();
    }

    /**
     * Get the primary category from the pivot table.
     */
    public function primaryCategory(): ?Category
    {
        return $this->categories()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get the brand that the product belongs to.
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
     * Get the variants for the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Get active variants for the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
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
     * Get the attribute values for the product.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get all inventories for the product (including variants).
     *
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the default warehouse inventory for the product.
     */
    public function inventory(): ?Inventory
    {
        return $this->inventories()
            ->whereNull('product_variant_id')
            ->whereNull('warehouse_id')
            ->first();
    }

    /**
     * Get product images ordered by sort order.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary product image.
     */
    public function primaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    /**
     * Get the primary image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function primaryImageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_image_media_id');
    }

    /**
     * Get YouTube videos for the product.
     *
     * @return HasMany<ProductVideo, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    /**
     * Get the primary product video.
     */
    public function primaryVideo(): ?ProductVideo
    {
        return $this->videos()->where('is_primary', true)->first();
    }

    /**
     * Get downloadable files for the product.
     *
     * @return HasMany<ProductDownload, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class)->orderBy('sort_order');
    }

    /**
     * Get preview downloadable files for the product.
     *
     * @return HasMany<ProductDownload, $this>
     */
    public function previewDownloads(): HasMany
    {
        return $this->downloads()->where('is_preview', true);
    }

    /**
     * Get service details for service products.
     *
     * @return HasOne<ProductService, $this>
     */
    public function service(): HasOne
    {
        return $this->hasOne(ProductService::class);
    }

    /**
     * Get bundle items for combo/bundle products.
     *
     * @return HasMany<ProductBundle, $this>
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'product_id');
    }

    /**
     * Get bundles that include this product.
     *
     * @return HasMany<ProductBundle, $this>
     */
    public function includedInBundles(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'included_product_id');
    }

    /**
     * Get subscription details for subscription products.
     *
     * @return HasOne<ProductSubscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(ProductSubscription::class);
    }

    /**
     * Get all reviews for the product.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get approved reviews for the product.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get related product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(ProductRelatedProduct::class);
    }

    /**
     * Get upsell product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function upsells(): HasMany
    {
        return $this->relatedProducts()->where('relation_type', 'upsell');
    }

    /**
     * Get cross-sell product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function crossSells(): HasMany
    {
        return $this->relatedProducts()->where('relation_type', 'cross_sell');
    }

    /**
     * Get accessory product links.
     *
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function accessories(): HasMany
    {
        return $this->relatedProducts()->where('relation_type', 'accessory');
    }

    /**
     * Get the collections this product belongs to.
     *
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    /**
     * Get product SEO data.
     *
     * @return HasOne<ProductSeo, $this>
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ProductSeo::class);
    }

    /**
     * Get product price tiers.
     *
     * @return HasMany<ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    /**
     * Get product price history records.
     *
     * @return HasMany<ProductPriceHistory, $this>
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /**
     * Get product specifications.
     *
     * @return HasMany<ProductSpecification, $this>
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    /**
     * Get product specifications for a specific group.
     *
     * @return HasMany<ProductSpecification, $this>
     */
    public function specificationsByGroup(string $group): HasMany
    {
        return $this->specifications()->where('group', $group);
    }

    /**
     * Get product documents.
     *
     * @return HasMany<ProductDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order');
    }

    /**
     * Get public product documents.
     *
     * @return HasMany<ProductDocument, $this>
     */
    public function publicDocuments(): HasMany
    {
        return $this->documents()->where('is_public', true);
    }

    /**
     * Get product FAQs.
     *
     * @return HasMany<ProductFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    /**
     * Get visible product FAQs.
     *
     * @return HasMany<ProductFaq, $this>
     */
    public function visibleFaqs(): HasMany
    {
        return $this->faqs()->where('is_visible', true);
    }

    /**
     * Get shipping profiles for the product.
     *
     * @return HasMany<ProductShippingProfile, $this>
     */
    public function shippingProfiles(): HasMany
    {
        return $this->hasMany(ProductShippingProfile::class);
    }

    /**
     * Get the tax class for the product.
     *
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    /**
     * Get the supplier for the product.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the weight unit for the product.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function weightUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'weight_unit_id');
    }

    /**
     * Get the dimension unit for the product.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function dimensionUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'dimension_unit_id');
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
     * @return HasMany<ProductImage, $this>
     */
    public function productImages(): HasMany
    {
        return $this->images();
    }

    /**
     * @return HasMany<ProductDownload, $this>
     */
    public function digitalFiles(): HasMany
    {
        return $this->downloads();
    }

    /**
     * @return HasMany<ProductBundle, $this>
     */
    public function comboItems(): HasMany
    {
        return $this->bundleItems();
    }

    /**
     * @return HasMany<ProductPriceTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->priceTiers();
    }

    /**
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function crossSellProducts(): HasMany
    {
        return $this->crossSells();
    }

    /**
     * @return HasMany<ProductRelatedProduct, $this>
     */
    public function upSellProducts(): HasMany
    {
        return $this->upsells();
    }

    /**
     * @return HasMany<ProductProvider, $this>
     */
    public function serviceProviders(): HasMany
    {
        return $this->hasMany(ProductProvider::class);
    }

    /**
     * @return HasMany<ProductReview, $this>
     */
    public function allReviews(): HasMany
    {
        return $this->reviews();
    }

    // -------------------------------------------------------------------------
    // Computed Properties
    // -------------------------------------------------------------------------

    /**
     * Average rating from approved reviews.
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->approvedReviews()->avg('rating');
    }

    /**
     * Count of approved reviews.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Calculate discount percentage.
     */
    public function discountPercentage(): ?float
    {
        if ($this->compare_at_price === null || $this->compare_at_price <= 0) {
            return null;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
    }

    /**
     * Check if product is on sale.
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    /**
     * Get profit margin.
     */
    public function profitMargin(): ?float
    {
        if ($this->cost_price === null || $this->cost_price <= 0) {
            return null;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    /**
     * Get combo total value (sum of included products).
     */
    public function comboTotalValue(): ?float
    {
        if ($this->product_type !== ProductType::Combo) {
            return null;
        }

        return $this->bundleItems->sum(function (ProductBundle $item): float {
            return $item->effective_price * $item->quantity;
        });
    }

    /**
     * Get combo savings amount.
     */
    public function comboSavings(): ?float
    {
        $totalValue = $this->comboTotalValue();

        if ($totalValue === null) {
            return null;
        }

        return max(0, $totalValue - (float) $this->price);
    }

    /**
     * Check if product requires shipping.
     */
    public function requiresShipping(): bool
    {
        $type = ProductType::tryFrom($this->product_type);

        return $type?->requiresShipping() ?? true;
    }

    /**
     * Check if product tracks inventory.
     */
    public function tracksInventory(): bool
    {
        if (! $this->track_inventory) {
            return false;
        }

        $type = $this->product_type instanceof ProductType
            ? $this->product_type
            : ProductType::tryFrom((string) $this->product_type);

        return $type?->tracksInventory() ?? true;
    }

    /**
     * Effective selling price (sale price when set, otherwise base price).
     */
    public function sellingPrice(): float
    {
        return (float) $this->price;
    }

    /**
     * Computed stock status for admin and storefront.
     */
    public function stockStatus(): string
    {
        if (! $this->tracksInventory()) {
            return 'not_tracked';
        }

        $available = $this->inventory()?->availableQuantity() ?? 0;

        if ($available > 0) {
            return $this->inventory()?->isLowStock() ? 'low_stock' : 'in_stock';
        }

        return $this->allow_backorders ? 'backorder' : 'out_of_stock';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope a query to search products by name, sku, or description.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('meta_keywords', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter products.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $q->search((string) $filters['search']);
            })
            ->when(! empty($filters['category_id']), function (Builder $q) use ($filters) {
                if (is_array($filters['category_id'])) {
                    $q->whereIn('category_id', $filters['category_id']);
                } else {
                    $q->where('category_id', $filters['category_id']);
                }
            })
            ->when(! empty($filters['brand_id']), function (Builder $q) use ($filters) {
                if (is_array($filters['brand_id'])) {
                    $q->whereIn('brand_id', $filters['brand_id']);
                } else {
                    $q->where('brand_id', $filters['brand_id']);
                }
            })
            ->when(! empty($filters['status']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['status'])
                    ? $filters['status']
                    : explode(',', (string) $filters['status']);

                $q->whereIn('status', $statuses);
            })
            ->when(! empty($filters['is_visible']), function (Builder $q) use ($filters): void {
                $values = is_array($filters['is_visible'])
                    ? $filters['is_visible']
                    : explode(',', (string) $filters['is_visible']);

                $booleans = [];
                if (in_array('visible', $values, true) || in_array('1', $values, true) || in_array(true, $values, true)) {
                    $booleans[] = true;
                }
                if (in_array('hidden', $values, true) || in_array('0', $values, true) || in_array(false, $values, true)) {
                    $booleans[] = false;
                }

                if ($booleans !== []) {
                    $q->whereIn('is_visible', $booleans);
                }
            })
            ->when(! empty($filters['is_featured']), function (Builder $q) use ($filters): void {
                $values = is_array($filters['is_featured'])
                    ? $filters['is_featured']
                    : explode(',', (string) $filters['is_featured']);

                $booleans = [];
                if (in_array('featured', $values, true) || in_array('1', $values, true)) {
                    $booleans[] = true;
                }
                if (in_array('not_featured', $values, true) || in_array('0', $values, true)) {
                    $booleans[] = false;
                }

                if ($booleans !== []) {
                    $q->whereIn('is_featured', $booleans);
                }
            })
            ->when(! empty($filters['category_ids']), function (Builder $q) use ($filters): void {
                $categoryIds = is_array($filters['category_ids'])
                    ? $filters['category_ids']
                    : explode(',', (string) $filters['category_ids']);

                $q->where(function (Builder $builder) use ($categoryIds): void {
                    $builder->whereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', function (Builder $categoryQuery) use ($categoryIds): void {
                            $categoryQuery->whereIn('categories.id', $categoryIds);
                        });
                });
            })
            ->when(! empty($filters['product_type']), function (Builder $q) use ($filters) {
                $types = is_array($filters['product_type']) ? $filters['product_type'] : [$filters['product_type']];
                $q->whereIn('product_type', $types);
            })
            ->when(! empty($filters['min_price']), function (Builder $q) use ($filters) {
                $q->where('price', '>=', (float) $filters['min_price']);
            })
            ->when(! empty($filters['max_price']), function (Builder $q) use ($filters) {
                $q->where('price', '<=', (float) $filters['max_price']);
            })
            ->when(! empty($filters['tag_ids']), function (Builder $q) use ($filters) {
                $tagIds = is_array($filters['tag_ids']) ? $filters['tag_ids'] : explode(',', (string) $filters['tag_ids']);
                $q->whereHas('tags', function (Builder $tq) use ($tagIds) {
                    $tq->whereIn('tags.id', $tagIds);
                });
            })
            ->when(! empty($filters['attribute_values']), function (Builder $q) use ($filters) {
                $attributeValues = is_array($filters['attribute_values'])
                    ? $filters['attribute_values']
                    : explode(',', (string) $filters['attribute_values']);
                $q->whereHas('attributeValues', function (Builder $aq) use ($attributeValues) {
                    $aq->whereIn('attribute_value_id', $attributeValues);
                });
            })
            ->when(! empty($filters['in_stock']), function (Builder $q) {
                $q->whereHas('inventories', function (Builder $iq) {
                    $iq->whereNull('product_variant_id')
                        ->whereNull('warehouse_id')
                        ->whereRaw('quantity > reserved_quantity');
                });
            })
            ->when(! empty($filters['has_variants']), function (Builder $q) {
                $q->has('variants');
            })
            ->when(! empty($filters['created_from']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['created_from']);
            })
            ->when(! empty($filters['created_to']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['created_to']);
            });
    }

    /**
     * Scope a query to only include visible products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Active)
            ->where('is_visible', true)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<Product>  $query
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
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include products of a specific type.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('product_type', $type);
    }

    /**
     * Scope a query to only include in-stock products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventories', function (Builder $q) {
            $q->whereNull('product_variant_id')
                ->whereNull('warehouse_id')
                ->whereRaw('quantity > reserved_quantity');
        });
    }

    /**
     * Scope a query to only include low stock products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('inventories', function (Builder $q) {
            $q->whereNull('product_variant_id')
                ->whereNull('warehouse_id')
                ->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold')
                ->whereRaw('quantity > 0');
        });
    }

    /**
     * Increment view count (no-op; column not present).
     */
    public function incrementViews(): void {}

    /**
     * Recalculate average rating (no-op; ratings computed via accessors).
     */
    public function recalculateRating(): void {}

    /**
     * Get structured data for SEO (Schema.org JSON-LD).
     *
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $type = ProductType::tryFrom($this->product_type);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $this->meta_description ?? $this->summary ?? $this->description,
            'sku' => $this->sku,
            'url' => $this->seo?->canonical_url ?? route('tenant.products.show', $this->slug),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $this->price,
                'priceCurrency' => config('app.currency', 'USD'),
                'availability' => ($this->inventory()?->availableQuantity() ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($this->brand) {
            $data['brand'] = [
                '@type' => 'Brand',
                'name' => $this->brand->name,
            ];
        }

        if ($this->primaryImageMedia) {
            $data['image'] = [$this->primaryImageMedia->getUrl()];
        }

        if ($this->average_rating > 0 && $this->review_count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $this->average_rating,
                'reviewCount' => (string) $this->review_count,
            ];
        }

        if ($this->mpn) {
            $data['mpn'] = $this->mpn;
        }

        if ($this->gtin) {
            $data['gtin'] = $this->gtin;
        }

        // Add video data if available
        $primaryVideo = $this->videos->firstWhere('is_primary', true) ?? $this->videos->first();
        if ($primaryVideo) {
            $data['video'] = [
                '@type' => 'VideoObject',
                'name' => $primaryVideo->title ?? $this->name,
                'description' => $primaryVideo->description ?? $this->description,
                'thumbnailUrl' => $primaryVideo->thumbnailUrl(),
                'contentUrl' => $primaryVideo->watchUrl(),
                'embedUrl' => $primaryVideo->embedUrl(),
            ];
        }

        // Service-specific schema
        if ($type === ProductType::Service) {
            $data['@type'] = 'Service';
            unset($data['offers']['availability']);

            if ($this->service?->duration_minutes) {
                $data['termsOfService'] = "Duration: {$this->service->duration_minutes} minutes";
            }
        }

        return $data;
    }
}
