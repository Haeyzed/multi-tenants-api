<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\ProductVisibility;
use App\Enums\Tenant\VariantStatus;
use Database\Factories\Tenant\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Sellable SKU entity — single source of truth for price, barcode, and inventory.
 *
 * @property int $id
 * @property int $product_id
 * @property string $title
 * @property string $sku
 * @property string|null $barcode
 * @property string|null $gtin
 * @property string|null $mpn
 * @property string $price
 * @property string|null $compare_at_price
 * @property string|null $cost_price
 * @property string|null $sale_price
 * @property Carbon|null $sale_starts_at
 * @property Carbon|null $sale_ends_at
 * @property bool $use_warehouse_pricing
 * @property string|null $weight
 * @property string|null $length
 * @property string|null $width
 * @property string|null $height
 * @property int|null $weight_unit_id
 * @property int|null $dimension_unit_id
 * @property int|null $image_media_id
 * @property VariantStatus $status
 * @property ProductVisibility $visibility
 * @property bool $is_default
 * @property int $position
 * @property string|null $hs_code
 * @property string|null $country_of_origin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read EloquentCollection<int, VariantOptionValue> $variantOptionValues
 * @property-read EloquentCollection<int, ProductOptionValue> $optionValues
 * @property-read EloquentCollection<int, ProductOption> $options
 * @property-read EloquentCollection<int, Inventory> $inventories
 * @property-read Media|null $imageMedia
 * @property-read Media|null $image
 * @property-read Unit|null $weightUnit
 * @property-read Unit|null $dimensionUnit
 *
 * @method static Builder<ProductVariant>|ProductVariant query()
 * @method static Builder<ProductVariant>|ProductVariant active()
 * @method static Builder<ProductVariant>|ProductVariant default()
 * @method static Builder<ProductVariant>|ProductVariant sellable()
 * @method static Builder<ProductVariant>|ProductVariant search(string $search)
 */
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'barcode',
        'gtin',
        'mpn',
        'price',
        'compare_at_price',
        'cost_price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'use_warehouse_pricing',
        'weight',
        'length',
        'width',
        'height',
        'weight_unit_id',
        'dimension_unit_id',
        'image_media_id',
        'status',
        'visibility',
        'is_default',
        'position',
        'hs_code',
        'country_of_origin',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'use_warehouse_pricing' => 'boolean',
            'weight' => 'decimal:4',
            'length' => 'decimal:4',
            'width' => 'decimal:4',
            'height' => 'decimal:4',
            'status' => VariantStatus::class,
            'visibility' => ProductVisibility::class,
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the catalog product that owns this variant.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get pivot records linking this variant to option values.
     *
     * @return HasMany<VariantOptionValue, $this>
     */
    public function variantOptionValues(): HasMany
    {
        return $this->hasMany(VariantOptionValue::class);
    }

    /**
     * Get option values selected for this variant.
     *
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        )
            ->withPivot(['product_option_id'])
            ->withTimestamps();
    }

    /**
     * Get options represented on this variant.
     *
     * @return BelongsToMany<ProductOption, $this>
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOption::class,
            'variant_option_values',
            'product_variant_id',
            'product_option_id'
        )
            ->withPivot(['product_option_value_id'])
            ->withTimestamps();
    }

    /**
     * Get inventory records for this variant.
     *
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get volume pricing tiers for this variant.
     *
     * @return HasMany<ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class, 'product_variant_id');
    }

    /**
     * Branch-specific selling prices when use_warehouse_pricing is enabled.
     *
     * @return HasMany<VariantWarehousePrice, $this>
     */
    public function warehousePrices(): HasMany
    {
        return $this->hasMany(VariantWarehousePrice::class, 'product_variant_id');
    }

    /**
     * Get inventory for an optional warehouse.
     */
    public function inventory(?int $warehouseId = null): ?Inventory
    {
        $query = $this->inventories();

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->first();
    }

    /**
     * Get the variant-specific image media.
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
     * Get the weight unit for this variant.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function weightUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'weight_unit_id');
    }

    /**
     * Get the dimension unit for this variant.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function dimensionUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'dimension_unit_id');
    }

    /**
     * Determine if the variant is sellable.
     */
    public function isSellable(): bool
    {
        return $this->status->isSellable()
            && $this->visibility->isPubliclyVisible()
            && $this->product->isPublished();
    }

    /**
     * Determine if a scheduled sale is currently active.
     */
    public function isSaleActive(?Carbon $at = null): bool
    {
        if ($this->sale_price === null) {
            return false;
        }

        $moment = $at ?? now();

        if ($this->sale_starts_at !== null && $moment->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at !== null && $moment->gt($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Base retail price before warehouse override (sale or regular).
     */
    public function baseSellingPrice(?Carbon $at = null): float
    {
        if ($this->isSaleActive($at)) {
            return (float) $this->sale_price;
        }

        return (float) $this->price;
    }

    /**
     * Resolve selling price for optional warehouse, quantity, and customer group.
     *
     * Priority: active tier (qty + group + schedule) → warehouse price → sale/regular.
     */
    public function resolveSellingPrice(
        ?int $warehouseId = null,
        int $quantity = 1,
        ?int $customerGroupId = null,
        ?Carbon $at = null
    ): float {
        $tierPrice = $this->resolveTierPrice($quantity, $customerGroupId, $at);
        if ($tierPrice !== null) {
            return $tierPrice;
        }

        if ($this->use_warehouse_pricing && $warehouseId !== null) {
            $warehousePrice = $this->warehousePrices()
                ->where('warehouse_id', $warehouseId)
                ->value('price');

            if ($warehousePrice !== null) {
                return (float) $warehousePrice;
            }
        }

        return $this->baseSellingPrice($at);
    }

    /**
     * Effective selling price (default resolution without warehouse context).
     */
    public function sellingPrice(?Carbon $at = null): float
    {
        return $this->baseSellingPrice($at);
    }

    /**
     * Resolve best matching quantity/customer-group tier price.
     */
    public function resolveTierPrice(
        int $quantity = 1,
        ?int $customerGroupId = null,
        ?Carbon $at = null
    ): ?float {
        if (! $this->relationLoaded('priceTiers')) {
            $this->load('priceTiers');
        }

        $moment = $at ?? now();

        $match = $this->priceTiers
            ->filter(function (ProductPriceTier $tier) use ($quantity, $customerGroupId, $moment): bool {
                if ($tier->customer_group_id !== null && $tier->customer_group_id !== $customerGroupId) {
                    return false;
                }

                if ($quantity < $tier->min_quantity) {
                    return false;
                }

                if ($tier->max_quantity !== null && $quantity > $tier->max_quantity) {
                    return false;
                }

                if ($tier->starts_at !== null && $moment->lt($tier->starts_at)) {
                    return false;
                }

                if ($tier->ends_at !== null && $moment->gt($tier->ends_at)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('min_quantity')
            ->first();

        return $match ? (float) $match->price : null;
    }

    /**
     * Determine if the variant is on sale (scheduled sale or compare-at merchandising).
     */
    public function isOnSale(?Carbon $at = null): bool
    {
        if ($this->isSaleActive($at)) {
            return true;
        }

        return $this->compare_at_price !== null
            && (float) $this->compare_at_price > $this->baseSellingPrice($at);
    }

    /**
     * Calculate discount percentage against compare-at or regular price.
     */
    public function discountPercentage(?Carbon $at = null): ?float
    {
        $reference = $this->compare_at_price !== null
            ? (float) $this->compare_at_price
            : (float) $this->price;

        if ($reference <= 0) {
            return null;
        }

        $selling = $this->baseSellingPrice($at);

        if ($selling >= $reference) {
            return null;
        }

        return round((($reference - $selling) / $reference) * 100, 2);
    }

    /**
     * Scope a query to only include active variants.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VariantStatus::Active);
    }

    /**
     * Scope a query to only include default variants.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include sellable variants.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeSellable(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('visibility', ProductVisibility::Visible)
            ->whereHas('product', function (Builder $productQuery): void {
                $productQuery->visible();
            });
    }

    /**
     * Scope a query to search variants by identifier fields.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search): void {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhere('gtin', 'like', "%{$search}%")
                ->orWhere('mpn', 'like', "%{$search}%");
        });
    }
}
