<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use App\Enums\Tenant\VariantStatus;
use App\Events\Tenant\ProductCreated;
use App\Events\Tenant\ProductUpdated;
use App\Models\Tenant\Media;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use App\Models\Tenant\ProductDownload;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\ProductOption;
use App\Models\Tenant\ProductOptionValue;
use App\Models\Tenant\ProductPriceTier;
use App\Models\Tenant\ProductProvider;
use App\Models\Tenant\ProductRelatedProduct;
use App\Models\Tenant\ProductService as ProductServiceConfig;
use App\Models\Tenant\ProductSubscription;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\ProductVideo;
use App\Models\Tenant\VariantOptionValue;
use App\Models\Tenant\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Manages catalog products and sellable variants within a tenant store.
 */
class ProductService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Paginate the products.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with([
                'brand',
                'categories',
                'tags',
                'labels',
                'defaultVariant.imageMedia',
                'images' => fn ($query) => $query->where('is_primary', true)->with('media'),
            ])
            ->withCount(['variants', 'reviews'])
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a product by ID for admin detail.
     */
    public function find(int $id): Product
    {
        return Product::query()
            ->with([
                'categories',
                'brand.logoMedia',
                'tags',
                'labels',
                'options.values',
                'variants.inventories.warehouse',
                'variants.imageMedia',
                'variants.priceTiers',
                'variants.variantOptionValues.option',
                'variants.variantOptionValues.optionValue',
                'defaultVariant.inventories.warehouse',
                'defaultVariant.imageMedia',
                'defaultVariant.priceTiers',
                'images.media',
                'videos',
                'downloads.media',
                'service',
                'subscription',
                'bundleItems.includedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'bundleItems.includedVariant.imageMedia',
                'providers.provider',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
                'reviews' => fn ($query) => $query->approved()->latest()->limit(10),
                'relatedProducts.relatedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'crossSellProducts.relatedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'upSellProducts.relatedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'seo.ogImageMedia',
                'seo.twitterImageMedia',
                'collections',
                'suppliers',
                'productSuppliers.supplier',
                'attributeSet',
                'taxClass',
                'creator',
                'updater',
                'approver',
            ])
            ->withCount(['variants', 'reviews'])
            ->findOrFail($id);
    }

    /**
     * Find product by slug for storefront.
     */
    public function findBySlug(string $slug): Product
    {
        return Product::query()
            ->with([
                'categories',
                'brand.logoMedia',
                'tags',
                'labels',
                'options.values',
                'activeVariants.inventories',
                'activeVariants.imageMedia',
                'activeVariants.variantOptionValues.optionValue',
                'defaultVariant.inventories',
                'defaultVariant.imageMedia',
                'images.media',
                'videos',
                'downloads.media',
                'service',
                'subscription',
                'bundleItems.includedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'bundleItems.includedVariant.imageMedia',
                'providers.provider',
                'reviews' => fn ($query) => $query->approved()->latest()->limit(10),
                'relatedProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
                'crossSellProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
                'upSellProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
                'seo',
            ])
            ->visible()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            [$productData, $nested] = $this->extractProductPayload($data);

            /** @var Product $product */
            $product = Product::query()->create($productData);

            $this->syncCategories($product, $nested['category_ids'], $nested['primary_category_id']);
            $this->syncTags($product, $nested['tag_ids']);
            $this->syncLabels($product, $nested['label_ids']);
            $this->syncCollections($product, $nested['collection_ids']);
            $this->syncProductSuppliers($product, $nested['suppliers']);
            $this->syncGallery($product, $nested['gallery']);
            $this->syncVideos($product, $nested['videos']);
            $this->syncAttributeValues($product, $nested['attribute_values']);
            $this->syncRelatedProducts($product, $nested['related_product_ids'], 'related');
            $this->syncRelatedProducts($product, $nested['cross_sell_product_ids'], 'cross_sell');
            $this->syncRelatedProducts($product, $nested['up_sell_product_ids'], 'upsell');
            $this->syncDownloads($product, $nested['downloads']);
            $this->syncBundleItems($product, $nested['bundle_items']);
            $this->syncProviders($product, $nested['providers']);

            if ($nested['service'] !== null) {
                $this->syncService($product, $nested['service']);
            }

            if ($nested['subscription'] !== null) {
                $this->syncSubscription($product, $nested['subscription']);
            }

            if ($nested['seo'] !== null) {
                $product->seo()->create($nested['seo']);
            }

            if ($nested['variants'] !== []) {
                $this->syncVariants($product, $nested['variants']);
            } elseif ($this->shouldCreateDefaultVariant($product->type, $nested)) {
                $this->createDefaultVariant($product, $nested);
            }

            if ($nested['options'] !== []) {
                $this->syncProductOptions($product, $nested['options']);
            }

            $product = $this->find($product->id);
            ProductCreated::dispatch($product);

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            [$productData, $nested] = $this->extractProductPayload($data, isUpdate: true);

            if ($productData !== []) {
                $product->update($productData);
            }

            if ($nested['category_ids'] !== null || $nested['primary_category_id'] !== null) {
                $this->syncCategories(
                    $product,
                    $nested['category_ids'] ?? [],
                    $nested['primary_category_id'],
                );
            }

            if ($nested['tag_ids'] !== null) {
                $this->syncTags($product, $nested['tag_ids']);
            }

            if ($nested['label_ids'] !== null) {
                $this->syncLabels($product, $nested['label_ids']);
            }

            if ($nested['collection_ids'] !== null) {
                $this->syncCollections($product, $nested['collection_ids']);
            }

            if ($nested['suppliers'] !== null) {
                $this->syncProductSuppliers($product, $nested['suppliers']);
            }

            if ($nested['gallery'] !== null) {
                $this->syncGallery($product, $nested['gallery']);
            }

            if ($nested['videos'] !== null) {
                $this->syncVideos($product, $nested['videos']);
            }

            if ($nested['attribute_values'] !== null) {
                $this->syncAttributeValues($product, $nested['attribute_values']);
            }

            if ($nested['related_product_ids'] !== null) {
                $this->syncRelatedProducts($product, $nested['related_product_ids'], 'related');
            }

            if ($nested['cross_sell_product_ids'] !== null) {
                $this->syncRelatedProducts($product, $nested['cross_sell_product_ids'], 'cross_sell');
            }

            if ($nested['up_sell_product_ids'] !== null) {
                $this->syncRelatedProducts($product, $nested['up_sell_product_ids'], 'upsell');
            }

            if ($nested['downloads'] !== null) {
                $this->syncDownloads($product, $nested['downloads']);
            }

            if ($nested['bundle_items'] !== null) {
                $this->syncBundleItems($product, $nested['bundle_items']);
            }

            if ($nested['providers'] !== null) {
                $this->syncProviders($product, $nested['providers']);
            }

            if ($nested['service'] !== null) {
                $this->syncService($product, $nested['service']);
            }

            if ($nested['subscription'] !== null) {
                $this->syncSubscription($product, $nested['subscription']);
            }

            if ($nested['seo'] !== null) {
                $product->seo()->updateOrCreate(['product_id' => $product->id], $nested['seo']);
            }

            if ($nested['variants'] !== null) {
                $this->syncVariants($product, $nested['variants']);
            } elseif ($this->shouldCreateDefaultVariant($product->type, $nested)) {
                if ($product->defaultVariant()->exists()) {
                    $defaultVariant = $product->defaultVariant()->first();

                    if ($defaultVariant instanceof ProductVariant) {
                        $this->updateVariant($defaultVariant, $this->buildDefaultVariantPayload($nested));
                    }
                } else {
                    $this->createDefaultVariant($product, $nested);
                }
            }

            if ($nested['options'] !== null) {
                $this->syncProductOptions($product, $nested['options']);
            }

            $product = $this->find($product->id);
            ProductUpdated::dispatch($product);

            return $product;
        });
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Delete multiple products by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Product::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Bulk update product fields.
     *
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $data
     */
    public function updateMany(array $ids, array $data): int
    {
        $updates = array_filter([
            'status' => $data['status'] ?? null,
            'visibility' => $data['visibility'] ?? null,
        ], fn ($value) => $value !== null);

        if ($updates === []) {
            return 0;
        }

        return Product::query()->whereIn('id', $ids)->update($updates);
    }

    /**
     * Force delete a product permanently.
     */
    public function forceDelete(Product $product): void
    {
        $product->forceDelete();
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(Product $product): Product
    {
        $product->restore();

        return $product->fresh() ?? $product;
    }

    /**
     * Restore multiple soft-deleted products by ID.
     *
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return Product::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Create a variant for a product.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            if (! empty($data['is_default'])) {
                $product->variants()->update(['is_default' => false]);
            }

            $inventoryData = $data['inventory'] ?? [];
            $priceTiers = $data['price_tiers'] ?? $data['pricing_tiers'] ?? [];
            $optionValueIds = $data['option_value_ids'] ?? null;
            unset($data['inventory'], $data['price_tiers'], $data['pricing_tiers'], $data['option_value_ids']);

            $data = $this->normalizeVariantFields($data);

            /** @var ProductVariant $variant */
            $variant = $product->variants()->create($data);

            if ($optionValueIds !== null) {
                $this->syncVariantOptionValues($variant, $optionValueIds);
            }

            $this->syncVariantInventory($variant, $inventoryData);
            $this->syncVariantPriceTiers($variant, $priceTiers);

            return $variant->load([
                'inventories.warehouse',
                'imageMedia',
                'priceTiers',
                'variantOptionValues.option',
                'variantOptionValues.optionValue',
            ]);
        });
    }

    /**
     * Update a product variant.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data): ProductVariant {
            if (! empty($data['is_default'])) {
                $variant->product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
            }

            $inventoryData = array_key_exists('inventory', $data) ? $data['inventory'] : null;
            $priceTiers = array_key_exists('price_tiers', $data)
                ? $data['price_tiers']
                : (array_key_exists('pricing_tiers', $data) ? $data['pricing_tiers'] : null);
            $optionValueIds = array_key_exists('option_value_ids', $data) ? $data['option_value_ids'] : null;
            unset($data['inventory'], $data['price_tiers'], $data['pricing_tiers'], $data['option_value_ids']);

            $data = $this->normalizeVariantFields($data);

            $variant->update($data);

            if ($optionValueIds !== null) {
                $this->syncVariantOptionValues($variant, $optionValueIds);
            }

            if ($inventoryData !== null) {
                $this->syncVariantInventory($variant, $inventoryData);
            }

            if ($priceTiers !== null) {
                $this->syncVariantPriceTiers($variant, $priceTiers);
            }

            return $variant->fresh([
                'inventories.warehouse',
                'imageMedia',
                'priceTiers',
                'variantOptionValues.option',
                'variantOptionValues.optionValue',
            ]);
        });
    }

    /**
     * Delete a product variant.
     */
    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }

    /**
     * Sync product suppliers with commercial terms.
     *
     * @param  list<array<string, mixed>>  $suppliers
     *
     * @throws Throwable
     */
    public function syncProductSuppliers(Product $product, array $suppliers): Product
    {
        return DB::transaction(function () use ($product, $suppliers): Product {
            if ($suppliers === []) {
                $product->suppliers()->detach();

                return $product->load('productSuppliers.supplier');
            }

            $syncData = [];
            $primarySupplierId = null;

            foreach ($suppliers as $supplierData) {
                $supplierId = (int) $supplierData['supplier_id'];

                if (! empty($supplierData['is_primary'])) {
                    $primarySupplierId = $supplierId;
                }

                $syncData[$supplierId] = [
                    'supplier_sku' => $supplierData['supplier_sku'] ?? null,
                    'supplier_cost' => $supplierData['supplier_cost'] ?? null,
                    'lead_time_days' => $supplierData['lead_time_days'] ?? null,
                    'minimum_quantity' => (int) ($supplierData['minimum_quantity'] ?? 1),
                    'is_primary' => ! empty($supplierData['is_primary']),
                ];
            }

            if ($primarySupplierId === null) {
                $firstSupplierId = array_key_first($syncData);

                if ($firstSupplierId !== null) {
                    $syncData[$firstSupplierId]['is_primary'] = true;
                }
            } else {
                foreach ($syncData as $supplierId => $pivot) {
                    $syncData[$supplierId]['is_primary'] = $supplierId === $primarySupplierId;
                }
            }

            $product->suppliers()->sync($syncData);

            return $product->load('productSuppliers.supplier');
        });
    }

    /**
     * Sync related, cross-sell, and upsell product links.
     *
     * @param  array{
     *     related_product_ids?: list<int>,
     *     cross_sell_product_ids?: list<int>,
     *     up_sell_product_ids?: list<int>
     * }  $relations
     *
     * @throws Throwable
     */
    public function syncProductRelations(Product $product, array $relations): Product
    {
        return DB::transaction(function () use ($product, $relations): Product {
            $this->syncRelatedProducts(
                $product,
                $relations['related_product_ids'] ?? [],
                'related',
            );
            $this->syncRelatedProducts(
                $product,
                $relations['cross_sell_product_ids'] ?? [],
                'cross_sell',
            );
            $this->syncRelatedProducts(
                $product,
                $relations['up_sell_product_ids'] ?? [],
                'upsell',
            );

            return $product->load([
                'relatedProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
                'crossSellProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
                'upSellProducts.relatedProduct' => fn ($query) => $query
                    ->with(['images' => fn ($imageQuery) => $imageQuery->where('is_primary', true)->with('media')])
                    ->visible(),
            ]);
        });
    }

    /**
     * Sync downloadable files for a digital product.
     *
     * @param  list<array<string, mixed>>  $downloads
     *
     * @throws Throwable
     */
    public function syncProductDownloads(Product $product, array $downloads): Product
    {
        return DB::transaction(function () use ($product, $downloads): Product {
            $this->syncDownloads($product, $downloads);

            return $product->load('downloads.media');
        });
    }

    /**
     * Sync bundle component items.
     *
     * @param  list<array<string, mixed>>  $bundleItems
     *
     * @throws Throwable
     */
    public function syncProductBundleItems(Product $product, array $bundleItems): Product
    {
        return DB::transaction(function () use ($product, $bundleItems): Product {
            $this->syncBundleItems($product, $bundleItems);

            return $product->load([
                'bundleItems.includedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'bundleItems.includedVariant.imageMedia',
            ]);
        });
    }

    /**
     * Sync service configuration and optional staff providers.
     *
     * @param  array{
     *     service: array<string, mixed>,
     *     providers?: list<array<string, mixed>|int>
     * }  $data
     *
     * @throws Throwable
     */
    public function syncProductService(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $this->syncService($product, $data['service']);

            if (array_key_exists('providers', $data)) {
                $this->syncProviders($product, $data['providers']);
            }

            return $product->load(['service', 'providers.provider']);
        });
    }

    /**
     * Sync subscription billing configuration.
     *
     * @param  array<string, mixed>  $subscription
     *
     * @throws Throwable
     */
    public function syncProductSubscription(Product $product, array $subscription): Product
    {
        return DB::transaction(function () use ($product, $subscription): Product {
            $this->syncSubscription($product, $subscription);

            return $product->load('subscription');
        });
    }

    /**
     * Sync variant-generating options and their values for a product.
     *
     * @param  list<array<string, mixed>>  $options
     *
     * @throws Throwable
     */
    public function syncProductOptions(Product $product, array $options): Product
    {
        return DB::transaction(function () use ($product, $options): Product {
            $keptOptionIds = [];

            foreach ($options as $index => $optionData) {
                $values = $optionData['values'] ?? [];
                $code = $optionData['code'] ?? Str::slug((string) $optionData['name'], '_');

                if (! empty($optionData['id'])) {
                    $option = ProductOption::query()
                        ->where('product_id', $product->id)
                        ->find($optionData['id']);

                    if ($option instanceof ProductOption) {
                        $option->update([
                            'name' => $optionData['name'],
                            'code' => $code,
                            'position' => $optionData['position'] ?? $index,
                        ]);
                    } else {
                        $option = ProductOption::query()->create([
                            'product_id' => $product->id,
                            'name' => $optionData['name'],
                            'code' => $code,
                            'position' => $optionData['position'] ?? $index,
                        ]);
                    }
                } else {
                    $option = ProductOption::query()->create([
                        'product_id' => $product->id,
                        'name' => $optionData['name'],
                        'code' => $code,
                        'position' => $optionData['position'] ?? $index,
                    ]);
                }

                $keptOptionIds[] = $option->id;
                $keptValueIds = [];

                foreach ($values as $valueIndex => $valueData) {
                    $valueCode = $valueData['code'] ?? Str::slug((string) $valueData['value'], '_');

                    if (! empty($valueData['id'])) {
                        $value = ProductOptionValue::query()
                            ->where('product_option_id', $option->id)
                            ->find($valueData['id']);

                        if ($value instanceof ProductOptionValue) {
                            $value->update([
                                'value' => $valueData['value'],
                                'code' => $valueCode,
                                'position' => $valueData['position'] ?? $valueIndex,
                            ]);
                            $keptValueIds[] = $value->id;

                            continue;
                        }
                    }

                    $value = $option->values()->create([
                        'value' => $valueData['value'],
                        'code' => $valueCode,
                        'position' => $valueData['position'] ?? $valueIndex,
                    ]);

                    $keptValueIds[] = $value->id;
                }

                $option->values()->whereNotIn('id', $keptValueIds)->delete();
            }

            $product->options()->whereNotIn('id', $keptOptionIds)->delete();

            return $product->load('options.values');
        });
    }

    /**
     * Generate variants from the cartesian product of option values.
     *
     * @param  array<string, mixed>  $defaults
     * @return Collection<int, ProductVariant>
     *
     * @throws Throwable
     */
    public function generateVariantsFromOptions(Product $product, array $defaults = []): Collection
    {
        return DB::transaction(function () use ($product, $defaults): Collection {
            $options = $product->options()->with('values')->orderBy('position')->get();

            if ($options->isEmpty()) {
                throw new RuntimeException('Add at least one product option with values before generating variants.');
            }

            if ($options->contains(fn (ProductOption $option): bool => $option->values->isEmpty())) {
                throw new RuntimeException('Every product option must have at least one value.');
            }

            $combinations = [[]];

            foreach ($options as $option) {
                $newCombinations = [];

                foreach ($combinations as $combination) {
                    foreach ($option->values as $value) {
                        $newCombinations[] = array_merge($combination, [$value]);
                    }
                }

                $combinations = $newCombinations;
            }

            $skipExisting = (bool) ($defaults['skip_existing'] ?? true);
            $created = collect();

            foreach ($combinations as $values) {
                /** @var list<ProductOptionValue> $values */
                $optionValueIds = collect($values)->pluck('id')->sort()->values()->all();

                if ($skipExisting && $this->findVariantByOptionValueIds($product, $optionValueIds) instanceof ProductVariant) {
                    continue;
                }

                $title = $product->name.' - '.collect($values)->pluck('value')->implode(' / ');
                $sku = $this->generateVariantSku($product, $values);

                $variant = $this->createVariant($product, [
                    'title' => $title,
                    'sku' => $sku,
                    'price' => $defaults['price'] ?? 0,
                    'compare_at_price' => $defaults['compare_at_price'] ?? null,
                    'cost_price' => $defaults['cost_price'] ?? null,
                    'is_default' => $product->variants()->count() === 0,
                    'option_value_ids' => $optionValueIds,
                    'inventory' => $defaults['inventory'] ?? [],
                ]);

                $created->push($variant);
            }

            return $created;
        });
    }

    /**
     * Get products for storefront with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getStorefrontProducts(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        return Product::query()
            ->with([
                'brand',
                'categories',
                'defaultVariant.inventories',
                'defaultVariant.imageMedia',
                'images' => fn ($query) => $query->where('is_primary', true)->with('media'),
            ])
            ->visible()
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get featured products for homepage.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getFeaturedProducts(int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->with([
                'brand',
                'defaultVariant.inventories',
                'defaultVariant.imageMedia',
                'images' => fn ($query) => $query->where('is_primary', true)->with('media'),
            ])
            ->visible()
            ->featured()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get related products for a product.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getRelatedProducts(Product $product, int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        $related = $product->relatedProducts()
            ->with([
                'relatedProduct.images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'relatedProduct.defaultVariant.inventories',
                'relatedProduct.defaultVariant.imageMedia',
            ])
            ->limit($limit)
            ->get()
            ->pluck('relatedProduct')
            ->filter();

        if ($related->count() >= $limit) {
            return $related->take($limit)->values();
        }

        $needed = $limit - $related->count();
        $primaryCategory = $product->primaryCategory();

        $additionalQuery = Product::query()
            ->with([
                'images' => fn ($query) => $query->where('is_primary', true)->with('media'),
                'defaultVariant.inventories',
                'defaultVariant.imageMedia',
            ])
            ->visible()
            ->where('id', '!=', $product->id)
            ->whereNotIn('id', $related->pluck('id')->filter()->all())
            ->latest()
            ->limit($needed);

        if ($primaryCategory !== null) {
            $additionalQuery->whereHas('categories', function (Builder $query) use ($primaryCategory): void {
                $query->where('categories.id', $primaryCategory->id);
            });
        }

        return $related->merge($additionalQuery->get())->values();
    }

    /**
     * @return array{total: int, active: int, draft: int, low_stock: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('status', ProductStatus::Active)->count(),
            'draft' => Product::query()->where('status', ProductStatus::Draft)->count(),
            'low_stock' => Product::query()
                ->whereHas('variants.inventories', function (Builder $query): void {
                    $query->whereNotNull('reorder_level')
                        ->whereColumn('available_quantity', '<=', 'reorder_level');
                })
                ->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Product::query()
            ->with([
                'defaultVariant:id,product_id,sku',
                'images' => fn ($query) => $query->where('is_primary', true)->with('media'),
            ])
            ->where('status', ProductStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Product $product): array => [
                'label' => sprintf(
                    '%s (%s)',
                    $product->name,
                    $product->defaultVariant?->sku ?? $product->slug,
                ),
                'value' => $product->id,
                'image_url' => $product->images->first()?->media?->getUrl(),
            ]);
    }

    /**
     * @param  list<int>|null  $ids
     * @return Collection<int, Product>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Product::query()
            ->with(['categories', 'brand', 'defaultVariant.inventories', 'tags'])
            ->latest();

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function extractProductPayload(array $data, bool $isUpdate = false): array
    {
        $hasGallery = array_key_exists('gallery', $data);
        $hasPrimaryImage = array_key_exists('primary_image_media_id', $data);

        $gallery = null;
        if ($hasGallery || $hasPrimaryImage) {
            $gallery = $this->buildGalleryPayload(
                $hasGallery ? (array) ($data['gallery'] ?? []) : [],
                $hasPrimaryImage ? ($data['primary_image_media_id'] ?? null) : null,
                $hasPrimaryImage,
            );
        } elseif (! $isUpdate) {
            $gallery = [];
        }

        $nested = [
            'tag_ids' => array_key_exists('tag_ids', $data) ? $data['tag_ids'] : ($isUpdate ? null : []),
            'label_ids' => array_key_exists('label_ids', $data) ? $data['label_ids'] : ($isUpdate ? null : []),
            'category_ids' => array_key_exists('category_ids', $data) ? $data['category_ids'] : ($isUpdate ? null : []),
            'primary_category_id' => $data['primary_category_id'] ?? $data['category_id'] ?? null,
            'collection_ids' => array_key_exists('collection_ids', $data) ? $data['collection_ids'] : ($isUpdate ? null : []),
            'suppliers' => array_key_exists('suppliers', $data) ? $data['suppliers'] : ($isUpdate ? null : []),
            'gallery' => $gallery,
            'videos' => array_key_exists('videos', $data) ? $data['videos'] : ($isUpdate ? null : []),
            'attribute_values' => array_key_exists('attribute_values', $data)
                ? $data['attribute_values']
                : ($isUpdate ? null : []),
            'related_product_ids' => array_key_exists('related_product_ids', $data)
                ? $data['related_product_ids']
                : ($isUpdate ? null : []),
            'cross_sell_product_ids' => array_key_exists('cross_sell_product_ids', $data)
                ? $data['cross_sell_product_ids']
                : ($isUpdate ? null : []),
            'up_sell_product_ids' => array_key_exists('up_sell_product_ids', $data)
                ? $data['up_sell_product_ids']
                : ($isUpdate ? null : []),
            'downloads' => array_key_exists('downloads', $data) || array_key_exists('digital_files', $data)
                ? ($data['downloads'] ?? $data['digital_files'] ?? [])
                : ($isUpdate ? null : []),
            'bundle_items' => array_key_exists('bundle_items', $data) || array_key_exists('combo_items', $data)
                ? ($data['bundle_items'] ?? $data['combo_items'] ?? [])
                : ($isUpdate ? null : []),
            'providers' => $this->normalizeProvidersPayload($data, $isUpdate),
            'service' => array_key_exists('service', $data) ? $data['service'] : ($isUpdate ? null : null),
            'subscription' => array_key_exists('subscription', $data) ? $data['subscription'] : ($isUpdate ? null : null),
            'seo' => array_key_exists('seo', $data) ? $data['seo'] : ($isUpdate ? null : null),
            'variants' => $isUpdate ? null : ($data['variants'] ?? []),
            'options' => $isUpdate ? null : ($data['options'] ?? []),
            'default_variant' => $data['default_variant'] ?? null,
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'] ?? null,
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'gtin' => $data['gtin'] ?? null,
            'mpn' => $data['mpn'] ?? null,
            'inventory' => $data['inventory'] ?? [],
            'name' => $data['name'] ?? null,
            'weight' => $data['weight'] ?? null,
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'weight_unit_id' => $data['weight_unit_id'] ?? null,
            'dimension_unit_id' => $data['dimension_unit_id'] ?? null,
        ];

        unset(
            $data['tag_ids'],
            $data['label_ids'],
            $data['category_ids'],
            $data['category_id'],
            $data['primary_category_id'],
            $data['collection_ids'],
            $data['suppliers'],
            $data['gallery'],
            $data['videos'],
            $data['attribute_values'],
            $data['related_product_ids'],
            $data['cross_sell_product_ids'],
            $data['up_sell_product_ids'],
            $data['downloads'],
            $data['digital_files'],
            $data['bundle_items'],
            $data['combo_items'],
            $data['providers'],
            $data['provider_ids'],
            $data['service'],
            $data['subscription'],
            $data['seo'],
            $data['variants'],
            $data['options'],
            $data['default_variant'],
            $data['sku'],
            $data['price'],
            $data['compare_at_price'],
            $data['cost_price'],
            $data['sale_price'],
            $data['barcode'],
            $data['gtin'],
            $data['mpn'],
            $data['inventory'],
            $data['pricing_tiers'],
            $data['price_tiers'],
            $data['primary_image_media_id'],
            $data['preview_media_id'],
            $data['weight'],
            $data['length'],
            $data['width'],
            $data['height'],
            $data['weight_unit'],
            $data['dimension_unit'],
            $data['weight_unit_id'],
            $data['dimension_unit_id'],
            $data['download_limit'],
            $data['download_expiry_days'],
            $data['duration_minutes'],
            $data['buffer_minutes'],
            $data['max_participants'],
            $data['location_type'],
            $data['service_location'],
            $data['allow_partial_combo'],
        );

        if (isset($data['product_type']) && ! isset($data['type'])) {
            $data['type'] = $data['product_type'];
        }
        unset($data['product_type']);

        if (isset($data['short_description']) && ! isset($data['summary'])) {
            $data['summary'] = $data['short_description'];
        }
        unset($data['short_description']);

        if (isset($data['taxable']) && ! isset($data['is_taxable'])) {
            $data['is_taxable'] = $data['taxable'];
        }
        unset($data['taxable']);

        if (array_key_exists('is_visible', $data) && ! isset($data['visibility'])) {
            $data['visibility'] = filter_var($data['is_visible'], FILTER_VALIDATE_BOOLEAN)
                ? ProductVisibility::Visible->value
                : ProductVisibility::Hidden->value;
        }
        unset($data['is_visible']);

        $data = $this->normalizePublishingFields($data);

        return [$data, $nested];
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function syncCategories(Product $product, array $categoryIds, mixed $primaryCategoryId = null): void
    {
        $ids = collect($categoryIds)
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($primaryCategoryId) {
            $primaryId = (int) $primaryCategoryId;
            if (! $ids->contains($primaryId)) {
                $ids->prepend($primaryId);
            }
        }

        $syncData = $ids->mapWithKeys(function (int $id, int $index) use ($primaryCategoryId): array {
            $isPrimary = $primaryCategoryId
                ? $id === (int) $primaryCategoryId
                : $index === 0;

            return [
                $id => [
                    'is_primary' => $isPrimary,
                    'sort_order' => $index,
                ],
            ];
        })->all();

        $product->categories()->sync($syncData);
    }

    /**
     * @param  list<int>  $tagIds
     */
    private function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    /**
     * @param  list<int>  $labelIds
     */
    private function syncLabels(Product $product, array $labelIds): void
    {
        $product->labels()->sync(
            collect($labelIds)
                ->values()
                ->mapWithKeys(fn (int $id, int $index): array => [
                    $id => ['sort_order' => $index],
                ])
                ->all(),
        );
    }

    /**
     * @param  list<int>  $collectionIds
     */
    private function syncCollections(Product $product, array $collectionIds): void
    {
        $product->collections()->sync(
            collect($collectionIds)
                ->values()
                ->mapWithKeys(fn (int $id, int $index): array => [
                    $id => ['sort_order' => $index],
                ])
                ->all()
        );
    }

    /**
     * @param  list<array<string, mixed>>  $gallery
     * @return list<array<string, mixed>>
     */
    private function buildGalleryPayload(array $gallery, mixed $primaryMediaId, bool $applyPrimary): array
    {
        if ($applyPrimary && $primaryMediaId) {
            $gallery = array_values(array_filter(
                $gallery,
                fn (array $item): bool => ! ($item['is_primary'] ?? false),
            ));

            array_unshift($gallery, [
                'media_id' => (int) $primaryMediaId,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        foreach ($gallery as $index => &$item) {
            $item['sort_order'] = $item['sort_order'] ?? $index;
        }

        return $gallery;
    }

    /**
     * @param  list<array<string, mixed>>  $gallery
     */
    private function syncGallery(Product $product, array $gallery): void
    {
        $product->images()->delete();

        foreach ($gallery as $index => $image) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $image['product_variant_id'] ?? null,
                'media_id' => $image['media_id'],
                'sort_order' => $image['sort_order'] ?? $index,
                'alt_text' => $image['alt_text'] ?? null,
                'caption' => $image['caption'] ?? null,
                'is_primary' => $image['is_primary'] ?? ($index === 0),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $nested
     */
    private function shouldCreateDefaultVariant(ProductType $type, array $nested): bool
    {
        if ($nested['default_variant'] !== null || $nested['sku'] !== null || $nested['price'] !== null) {
            return true;
        }

        return ! $type->requiresVariants();
    }

    /**
     * @param  array<string, mixed>  $nested
     */
    private function createDefaultVariant(Product $product, array $nested): ProductVariant
    {
        return $this->createVariant($product, $this->buildDefaultVariantPayload($nested));
    }

    /**
     * @param  array<string, mixed>  $nested
     * @return array<string, mixed>
     */
    private function buildDefaultVariantPayload(array $nested): array
    {
        $default = is_array($nested['default_variant']) ? $nested['default_variant'] : [];

        return array_filter([
            'title' => $default['title'] ?? $default['name'] ?? $nested['name'] ?? 'Default',
            'sku' => $default['sku'] ?? $nested['sku'] ?? null,
            'barcode' => $default['barcode'] ?? $nested['barcode'] ?? null,
            'gtin' => $default['gtin'] ?? $nested['gtin'] ?? null,
            'mpn' => $default['mpn'] ?? $nested['mpn'] ?? null,
            'price' => $default['price'] ?? $nested['price'] ?? 0,
            'compare_at_price' => $default['compare_at_price'] ?? $nested['compare_at_price'] ?? null,
            'cost_price' => $default['cost_price'] ?? $nested['cost_price'] ?? null,
            'weight' => $default['weight'] ?? $nested['weight'] ?? null,
            'length' => $default['length'] ?? $nested['length'] ?? null,
            'width' => $default['width'] ?? $nested['width'] ?? null,
            'height' => $default['height'] ?? $nested['height'] ?? null,
            'weight_unit_id' => $default['weight_unit_id'] ?? $nested['weight_unit_id'] ?? null,
            'dimension_unit_id' => $default['dimension_unit_id'] ?? $nested['dimension_unit_id'] ?? null,
            'image_media_id' => $default['image_media_id'] ?? null,
            'status' => $default['status'] ?? VariantStatus::Active->value,
            'visibility' => $default['visibility'] ?? ProductVisibility::Visible->value,
            'is_default' => true,
            'position' => $default['position'] ?? $default['sort_order'] ?? 0,
            'inventory' => $default['inventory'] ?? $nested['inventory'] ?? [],
            'price_tiers' => $default['price_tiers'] ?? $default['pricing_tiers'] ?? [],
            'option_value_ids' => $default['option_value_ids'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            if (! empty($variantData['id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->find($variantData['id']);

                if ($variant instanceof ProductVariant) {
                    $this->updateVariant($variant, $variantData);

                    continue;
                }
            }

            unset($variantData['id']);
            $this->createVariant($product, $variantData);
        }
    }

    /**
     * @param  list<int>  $optionValueIds
     */
    private function syncVariantOptionValues(ProductVariant $variant, array $optionValueIds): void
    {
        $variant->variantOptionValues()->delete();

        foreach ($optionValueIds as $optionValueId) {
            $optionValue = ProductOptionValue::query()->find($optionValueId);

            if (! $optionValue instanceof ProductOptionValue) {
                continue;
            }

            VariantOptionValue::query()->create([
                'product_variant_id' => $variant->id,
                'product_option_id' => $optionValue->product_option_id,
                'product_option_value_id' => $optionValue->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $inventoryData
     */
    private function syncVariantInventory(ProductVariant $variant, array $inventoryData): void
    {
        if ($inventoryData === []) {
            return;
        }

        $records = array_is_list($inventoryData) ? $inventoryData : [$inventoryData];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $warehouseId = $this->resolveWarehouseId($record);
            $this->inventoryService->upsertForVariant($variant, $warehouseId, $record);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     */
    private function syncVariantPriceTiers(ProductVariant $variant, array $tiers): void
    {
        $variant->priceTiers()->delete();

        foreach ($tiers as $tier) {
            unset($tier['id'], $tier['variant_id'], $tier['product_id']);
            $tier['product_variant_id'] = $variant->id;

            ProductPriceTier::query()->create($tier);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     */
    private function syncVideos(Product $product, array $videos): void
    {
        $product->videos()->delete();

        foreach ($videos as $index => $video) {
            $videoId = $video['video_id'] ?? $this->extractYouTubeId((string) ($video['video_url'] ?? ''));

            if ($videoId === null) {
                continue;
            }

            ProductVideo::query()->create([
                'product_id' => $product->id,
                'provider' => $video['provider'] ?? 'youtube',
                'video_url' => $video['video_url'],
                'video_id' => $videoId,
                'title' => $video['title'] ?? null,
                'description' => $video['description'] ?? null,
                'sort_order' => $video['sort_order'] ?? $index,
                'is_primary' => $video['is_primary'] ?? ($index === 0),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $attributeValues
     */
    private function syncAttributeValues(Product $product, array $attributeValues): void
    {
        $product->attributeValues()->delete();

        foreach ($attributeValues as $index => $attributeValue) {
            $product->attributeValues()->create([
                'attribute_id' => $attributeValue['attribute_id'],
                'attribute_value_id' => $attributeValue['attribute_value_id'] ?? null,
                'custom_value' => $attributeValue['custom_value'] ?? null,
                'sort_order' => $attributeValue['sort_order'] ?? $index,
            ]);
        }
    }

    /**
     * @param  list<int>  $relatedIds
     */
    private function syncRelatedProducts(Product $product, array $relatedIds, string $relationType): void
    {
        ProductRelatedProduct::query()
            ->where('product_id', $product->id)
            ->where('relation_type', $relationType)
            ->delete();

        foreach ($relatedIds as $index => $relatedId) {
            ProductRelatedProduct::query()->create([
                'product_id' => $product->id,
                'related_product_id' => $relatedId,
                'relation_type' => $relationType,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $downloads
     */
    private function syncDownloads(Product $product, array $downloads): void
    {
        $product->downloads()->delete();

        foreach ($downloads as $index => $file) {
            $media = Media::query()->find($file['media_id']);
            $fileName = $file['file_name'] ?? $media?->file_name ?? $media?->name ?? 'download';

            ProductDownload::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $file['product_variant_id'] ?? null,
                'media_id' => $file['media_id'],
                'file_name' => $fileName,
                'display_name' => $file['display_name'] ?? null,
                'description' => $file['description'] ?? null,
                'download_limit' => $file['download_limit'] ?? null,
                'download_expiry_days' => $file['download_expiry_days'] ?? null,
                'sort_order' => $file['sort_order'] ?? $index,
                'is_preview' => $file['is_preview'] ?? false,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $bundleItems
     */
    private function syncBundleItems(Product $product, array $bundleItems): void
    {
        $product->bundleItems()->delete();

        foreach ($bundleItems as $index => $item) {
            ProductBundle::query()->create([
                'product_id' => $product->id,
                'included_product_id' => $item['included_product_id'],
                'included_variant_id' => $item['included_variant_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'is_optional' => $item['is_optional'] ?? false,
                'discount_percentage' => $item['discount_percentage'] ?? null,
                'fixed_price' => $item['fixed_price'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>|int>  $providers
     */
    private function syncProviders(Product $product, array $providers): void
    {
        $product->providers()->delete();

        $primaryProviderId = null;

        foreach ($providers as $provider) {
            if (is_array($provider) && ! empty($provider['is_primary'])) {
                $primaryProviderId = (int) $provider['provider_id'];
            }
        }

        foreach ($providers as $index => $provider) {
            if (is_array($provider)) {
                $providerId = (int) $provider['provider_id'];
                $isPrimary = $primaryProviderId !== null
                    ? $providerId === $primaryProviderId
                    : $index === 0;

                ProductProvider::query()->create([
                    'product_id' => $product->id,
                    'provider_id' => $providerId,
                    'is_primary' => $provider['is_primary'] ?? $isPrimary,
                    'commission_rate' => $provider['commission_rate'] ?? null,
                ]);

                continue;
            }

            ProductProvider::query()->create([
                'product_id' => $product->id,
                'provider_id' => (int) $provider,
                'is_primary' => $primaryProviderId !== null
                    ? (int) $provider === $primaryProviderId
                    : $index === 0,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $service
     */
    private function syncService(Product $product, array $service): void
    {
        ProductServiceConfig::query()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'duration_minutes' => $service['duration_minutes'],
                'buffer_minutes_before' => $service['buffer_minutes_before'] ?? 0,
                'buffer_minutes_after' => $service['buffer_minutes_after'] ?? 0,
                'max_participants' => $service['max_participants'] ?? null,
                'location_type' => $service['location_type'] ?? 'any',
                'location_address' => $service['location_address'] ?? null,
                'meeting_url' => $service['meeting_url'] ?? null,
                'requires_confirmation' => $service['requires_confirmation'] ?? false,
                'cancellation_hours' => $service['cancellation_hours'] ?? 24,
                'instructions' => $service['instructions'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    private function syncSubscription(Product $product, array $subscription): void
    {
        ProductSubscription::query()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'interval' => $subscription['interval'],
                'interval_count' => $subscription['interval_count'] ?? 1,
                'trial_days' => $subscription['trial_days'] ?? 0,
                'trial_price' => $subscription['trial_price'] ?? null,
                'billing_cycles' => $subscription['billing_cycles'] ?? null,
                'prorate' => $subscription['prorate'] ?? true,
                'allow_pause' => $subscription['allow_pause'] ?? true,
                'allow_cancel_anytime' => $subscription['allow_cancel_anytime'] ?? true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>|null
     */
    private function normalizeProvidersPayload(array $data, bool $isUpdate): ?array
    {
        if (array_key_exists('providers', $data)) {
            return $data['providers'];
        }

        if (array_key_exists('provider_ids', $data)) {
            return collect($data['provider_ids'] ?? [])
                ->filter()
                ->map(fn (mixed $id): array => ['provider_id' => (int) $id])
                ->values()
                ->all();
        }

        return $isUpdate ? null : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeVariantFields(array $data): array
    {
        if (isset($data['name']) && ! isset($data['title'])) {
            $data['title'] = $data['name'];
        }
        unset($data['name']);

        if (isset($data['sort_order']) && ! isset($data['position'])) {
            $data['position'] = $data['sort_order'];
        }
        unset($data['sort_order']);

        if (isset($data['is_active']) && ! isset($data['status'])) {
            $data['status'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                ? VariantStatus::Active->value
                : VariantStatus::Inactive->value;
        }
        unset($data['is_active']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePublishingFields(array $data): array
    {
        if (array_key_exists('status', $data)) {
            $status = $data['status'] instanceof ProductStatus
                ? $data['status']
                : ProductStatus::tryFrom((string) $data['status']);

            if ($status instanceof ProductStatus) {
                $data['status'] = $status->value;

                if ($status !== ProductStatus::Active) {
                    $data['visibility'] = ProductVisibility::Hidden->value;
                } elseif (! isset($data['visibility'])) {
                    $data['visibility'] = ProductVisibility::Visible->value;
                }

                if ($status === ProductStatus::Active && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $inventoryData
     */
    private function resolveWarehouseId(array $inventoryData): int
    {
        if (! empty($inventoryData['warehouse_id'])) {
            return (int) $inventoryData['warehouse_id'];
        }

        $warehouseId = Warehouse::query()->where('is_primary', true)->value('id');

        if ($warehouseId === null) {
            $warehouseId = Warehouse::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('id');
        }

        if ($warehouseId === null) {
            throw new RuntimeException('warehouse_id is required for inventory updates and no default warehouse is configured.');
        }

        return (int) $warehouseId;
    }

    /**
     * @param  list<int>  $optionValueIds
     */
    private function findVariantByOptionValueIds(Product $product, array $optionValueIds): ?ProductVariant
    {
        $sortedIds = collect($optionValueIds)->sort()->values()->all();

        if ($sortedIds === []) {
            return null;
        }

        return $product->variants()
            ->with('variantOptionValues')
            ->get()
            ->first(function (ProductVariant $variant) use ($sortedIds): bool {
                $variantIds = $variant->variantOptionValues
                    ->pluck('product_option_value_id')
                    ->sort()
                    ->values()
                    ->all();

                return $variantIds === $sortedIds;
            });
    }

    /**
     * @param  list<ProductOptionValue>  $values
     */
    private function generateVariantSku(Product $product, array $values): string
    {
        $base = Str::upper(Str::slug($product->slug, '-'));
        $suffix = collect($values)
            ->pluck('code')
            ->map(fn (string $code): string => Str::upper($code))
            ->implode('-');

        $sku = $suffix !== '' ? "{$base}-{$suffix}" : $base;
        $candidate = $sku;
        $counter = 1;

        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
            $candidate = "{$sku}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    private function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
