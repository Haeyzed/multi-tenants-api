<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Variant option for a product (size, color, etc.).
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $sku
 * @property float $price
 * @property float|null $compare_at_price
 * @property float|null $cost_price
 * @property bool $is_default
 * @property bool $is_active
 * @property int|null $image_media_id
 * @property int $sort_order
 * @property string|null $barcode
 * @property float|null $weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read Collection<int, AttributeValue> $attributeValues
 * @property-read Collection<int, Inventory> $inventories
 * @property-read Collection<int, ProductImage> $images
 * @property-read Collection<int, ProductPriceTier> $priceTiers
 * @property-read Media|null $imageMedia
 * @property-read Media|null $image
 * @property-read float $final_price
 * @property-read bool $is_in_stock
 */
class ProductVariant extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'is_default',
        'is_active',
        'image_media_id',
        'sort_order',
        'barcode',
        'weight',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'weight' => 'decimal:3',
        ];
    }

    /**
     * Get the product that this variant belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<AttributeValue, $this>
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values')
            ->withPivot(['attribute_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get inventory for an optional warehouse.
     */
    public function inventory(?int $warehouseId = null): ?Inventory
    {
        $query = $this->inventories();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->first();
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * @return HasMany<ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class, 'variant_id');
    }

    /**
     * Get the variant image media.
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
     * @return HasMany<ProductPriceTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->priceTiers();
    }

    /**
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search variants by name or sku.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    public function getIsInStockAttribute(): bool
    {
        $inventory = $this->inventory();

        if (! $inventory) {
            return ! $this->product->track_inventory || $this->product->allow_backorders;
        }

        return $inventory->available_quantity > 0 || $this->product->allow_backorders;
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
     * Check if variant is on sale.
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }
}
