<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Events\Tenant\ProductCreated;
use App\Events\Tenant\ProductUpdated;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductComboItem;
use App\Models\Tenant\ProductDigitalFile;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\ProductPricingTier;
use App\Models\Tenant\ProductRelation;
use App\Models\Tenant\ProductServiceProvider;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\ProductVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages products within a tenant flash-sale store.
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
                'category',
                'categories',
                'brand',
                'tags',
                'inventory',
                'primaryImageMedia',
            ])
            ->withCount(['variants', 'reviews'])
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a product by ID.
     */
    public function find(int $id): Product
    {
        return Product::query()
            ->with([
                'category',
                'categories',
                'brand.logoMedia',
                'tags',
                'variants.inventory',
                'variants.imageMedia',
                'variants.pricingTiers',
                'inventory',
                'productImages.media',
                'primaryImageMedia',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
                'reviews' => fn ($q) => $q->approved()->latest()->limit(10),
                'relatedProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia'),
                'crossSellProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia'),
                'upSellProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia'),
                'pricingTiers',
                'seo',
                'collections',
                'digitalFiles.media',
                'previewMedia',
                'comboItems.includedProduct.primaryImageMedia',
                'comboItems.includedVariant',
                'serviceProviders.provider',
                'videos',
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
                'category',
                'brand.logoMedia',
                'tags',
                'variants.inventory',
                'variants.imageMedia',
                'inventory',
                'productImages.media',
                'primaryImageMedia',
                'reviews' => fn ($q) => $q->approved()->latest()->limit(10),
                'relatedProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia')->visible(),
                'crossSellProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia')->visible(),
                'upSellProducts.relatedProduct' => fn ($q) => $q->with('primaryImageMedia')->visible(),
                'pricingTiers',
                'digitalFiles.media',
                'previewMedia',
                'comboItems.includedProduct.primaryImageMedia',
                'comboItems.includedVariant',
                'serviceProviders.provider',
                'videos',
            ])
            ->where('slug', $slug)
            ->where('is_visible', true)
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
            $productType = ProductType::tryFrom($data['product_type'] ?? 'standard');

            // Extract type-specific data
            $tagIds = $data['tag_ids'] ?? [];
            $categoryIds = $data['category_ids'] ?? [];
            $gallery = $data['gallery'] ?? [];
            $videos = $data['videos'] ?? [];
            $attributeValues = $data['attribute_values'] ?? [];
            $relatedProductIds = $data['related_product_ids'] ?? [];
            $crossSellProductIds = $data['cross_sell_product_ids'] ?? [];
            $upSellProductIds = $data['up_sell_product_ids'] ?? [];
            $pricingTiers = $data['pricing_tiers'] ?? [];
            $seoData = $data['seo'] ?? null;
            $collectionIds = $data['collection_ids'] ?? [];

            // Type-specific data
            $digitalFiles = $data['digital_files'] ?? [];
            $comboItems = $data['combo_items'] ?? [];
            $providerIds = $data['provider_ids'] ?? [];
            $inventoryData = $data['inventory'] ?? [];
            $variants = $data['variants'] ?? [];

            // Clean data for product creation
            unset(
                $data['tag_ids'],
                $data['category_ids'],
                $data['gallery'],
                $data['videos'],
                $data['attribute_values'],
                $data['related_product_ids'],
                $data['cross_sell_product_ids'],
                $data['up_sell_product_ids'],
                $data['pricing_tiers'],
                $data['seo'],
                $data['collection_ids'],
                $data['digital_files'],
                $data['combo_items'],
                $data['provider_ids'],
                $data['inventory'],
                $data['variants']
            );

            $data = $this->normalizePublishingFields($data);

            /** @var Product $product */
            $product = Product::query()->create($data);

            // Sync basic relations
            $this->syncCategories($product, $categoryIds, $data['category_id'] ?? null);

            if ($tagIds !== []) {
                $product->tags()->sync($tagIds);
            }

            if ($collectionIds !== []) {
                $product->collections()->sync(
                    collect($collectionIds)
                        ->values()
                        ->mapWithKeys(fn (int $id, int $index): array => [
                            $id => ['sort_order' => $index],
                        ])
                        ->all()
                );
            }

            $this->syncGallery($product, $gallery);
            $this->syncVideos($product, $videos);
            $this->syncAttributeValues($product, $attributeValues);
            $this->syncRelations($product, $relatedProductIds, 'related');
            $this->syncRelations($product, $crossSellProductIds, 'cross_sell');
            $this->syncRelations($product, $upSellProductIds, 'up_sell');
            $this->syncPricingTiers($product, $pricingTiers);

            if ($seoData) {
                $product->seo()->create($seoData);
            }

            // Type-specific handling
            match ($productType) {
                ProductType::Standard => $this->handleStandardProduct($product, $inventoryData),
                ProductType::Digital => $this->handleDigitalProduct($product, $digitalFiles),
                ProductType::Service => $this->handleServiceProduct($product, $providerIds),
                ProductType::Combo => $this->handleComboProduct($product, $comboItems),
                default => null,
            };

            if ($variants !== []) {
                foreach ($variants as $variantData) {
                    $this->createVariant($product, $variantData);
                }
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
            $productType = ProductType::tryFrom($data['product_type'] ?? $product->product_type);

            // Extract relation data
            $tagIds = $data['tag_ids'] ?? null;
            $categoryIds = $data['category_ids'] ?? null;
            $gallery = $data['gallery'] ?? null;
            $videos = $data['videos'] ?? null;
            $attributeValues = $data['attribute_values'] ?? null;
            $relatedProductIds = $data['related_product_ids'] ?? null;
            $crossSellProductIds = $data['cross_sell_product_ids'] ?? null;
            $upSellProductIds = $data['up_sell_product_ids'] ?? null;
            $pricingTiers = $data['pricing_tiers'] ?? null;
            $seoData = $data['seo'] ?? null;
            $collectionIds = $data['collection_ids'] ?? null;

            // Type-specific data
            $digitalFiles = $data['digital_files'] ?? null;
            $comboItems = $data['combo_items'] ?? null;
            $providerIds = $data['provider_ids'] ?? null;
            $inventoryData = $data['inventory'] ?? null;
            $variants = $data['variants'] ?? null;

            unset(
                $data['tag_ids'],
                $data['category_ids'],
                $data['gallery'],
                $data['videos'],
                $data['attribute_values'],
                $data['related_product_ids'],
                $data['cross_sell_product_ids'],
                $data['up_sell_product_ids'],
                $data['pricing_tiers'],
                $data['seo'],
                $data['collection_ids'],
                $data['digital_files'],
                $data['combo_items'],
                $data['provider_ids'],
                $data['inventory'],
                $data['variants']
            );

            $data = $this->normalizePublishingFields($data);

            $product->update($data);

            // Sync relations
            if ($categoryIds !== null || array_key_exists('category_id', $data)) {
                $this->syncCategories(
                    $product,
                    $categoryIds ?? [],
                    $data['category_id'] ?? $product->category_id,
                );
            }

            if ($tagIds !== null) {
                $product->tags()->sync($tagIds);
            }

            if ($collectionIds !== null) {
                $product->collections()->sync(
                    collect($collectionIds)
                        ->values()
                        ->mapWithKeys(fn (int $id, int $index): array => [
                            $id => ['sort_order' => $index],
                        ])
                        ->all()
                );
            }

            if ($gallery !== null) {
                $this->syncGallery($product, $gallery);
            }

            if ($videos !== null) {
                $this->syncVideos($product, $videos);
            }

            if ($attributeValues !== null) {
                $this->syncAttributeValues($product, $attributeValues);
            }

            if ($relatedProductIds !== null) {
                $this->syncRelations($product, $relatedProductIds, 'related');
            }

            if ($crossSellProductIds !== null) {
                $this->syncRelations($product, $crossSellProductIds, 'cross_sell');
            }

            if ($upSellProductIds !== null) {
                $this->syncRelations($product, $upSellProductIds, 'up_sell');
            }

            if ($pricingTiers !== null) {
                $this->syncPricingTiers($product, $pricingTiers);
            }

            if ($seoData !== null) {
                $product->seo()->updateOrCreate(['product_id' => $product->id], $seoData);
            }

            // Type-specific handling
            match ($productType) {
                ProductType::Standard => $this->handleStandardProduct($product, $inventoryData ?? []),
                ProductType::Digital => $this->handleDigitalProduct($product, $digitalFiles ?? []),
                ProductType::Service => $this->handleServiceProduct($product, $providerIds ?? []),
                ProductType::Combo => $this->handleComboProduct($product, $comboItems ?? []),
                default => null,
            };

            if ($variants !== null) {
                foreach ($variants as $variantData) {
                    if (! empty($variantData['id'])) {
                        $variant = ProductVariant::query()
                            ->where('product_id', $product->id)
                            ->find($variantData['id']);

                        if ($variant) {
                            $this->updateVariant($variant, $variantData);

                            continue;
                        }
                    }

                    unset($variantData['id']);
                    $this->createVariant($product, $variantData);
                }
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

        return $product->fresh();
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

    // -------------------------------------------------------------------------
    // Variants
    // -------------------------------------------------------------------------

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
            $pricingTiers = $data['pricing_tiers'] ?? [];
            unset($data['inventory'], $data['pricing_tiers']);

            /** @var ProductVariant $variant */
            $variant = $product->variants()->create($data);

            $this->inventoryService->upsertForProduct(
                $product,
                $inventoryData,
                $variant->id,
            );

            foreach ($pricingTiers as $tier) {
                $tier['variant_id'] = $variant->id;
                $product->pricingTiers()->create($tier);
            }

            return $variant->load(['inventory', 'imageMedia', 'pricingTiers']);
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

            $inventoryData = $data['inventory'] ?? null;
            $pricingTiers = $data['pricing_tiers'] ?? null;
            unset($data['inventory'], $data['pricing_tiers']);

            $variant->update($data);

            if ($inventoryData !== null) {
                $this->inventoryService->upsertForProduct($variant->product, $inventoryData, $variant->id);
            }

            if ($pricingTiers !== null) {
                $variant->pricingTiers()->delete();
                foreach ($pricingTiers as $tier) {
                    $tier['variant_id'] = $variant->id;
                    $variant->product->pricingTiers()->create($tier);
                }
            }

            return $variant->fresh(['inventory', 'imageMedia', 'pricingTiers']);
        });
    }

    /**
     * Delete a product variant.
     */
    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }

    // -------------------------------------------------------------------------
    // Storefront
    // -------------------------------------------------------------------------

    /**
     * Get products for storefront with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getStorefrontProducts(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'inventory', 'primaryImageMedia'])
            ->visible();

        return $query->filter($filters)->paginate($perPage);
    }

    /**
     * Get featured products for homepage.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getFeaturedProducts(int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->with(['brand', 'inventory', 'primaryImageMedia'])
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
            ->with('relatedProduct.primaryImageMedia', 'relatedProduct.inventory')
            ->limit($limit)
            ->get()
            ->pluck('relatedProduct');

        if ($related->count() >= $limit) {
            return $related;
        }

        $needed = $limit - $related->count();

        $additional = Product::query()
            ->with(['primaryImageMedia', 'inventory'])
            ->visible()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->whereNotIn('id', $related->pluck('id'))
            ->latest()
            ->limit($needed)
            ->get();

        return $related->merge($additional);
    }

    // -------------------------------------------------------------------------
    // Type-specific Handlers
    // -------------------------------------------------------------------------

    /**
     * Handle standard product creation/update.
     */
    private function handleStandardProduct(Product $product, array $inventoryData): void
    {
        if ($inventoryData !== []) {
            $this->inventoryService->upsertForProduct($product, $inventoryData);
        }
    }

    /**
     * Handle digital product creation/update.
     */
    private function handleDigitalProduct(Product $product, array $digitalFiles): void
    {
        if ($digitalFiles === []) {
            return;
        }

        $product->digitalFiles()->delete();

        foreach ($digitalFiles as $index => $file) {
            ProductDigitalFile::query()->create([
                'product_id' => $product->id,
                'media_id' => $file['media_id'],
                'file_name' => $file['file_name'],
                'sort_order' => $file['sort_order'] ?? $index,
            ]);
        }
    }

    /**
     * Handle service product creation/update.
     */
    private function handleServiceProduct(Product $product, array $providerIds): void
    {
        if ($providerIds === []) {
            return;
        }

        $product->serviceProviders()->delete();

        foreach ($providerIds as $index => $providerId) {
            ProductServiceProvider::query()->create([
                'product_id' => $product->id,
                'provider_id' => $providerId,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Handle combo product creation/update.
     */
    private function handleComboProduct(Product $product, array $comboItems): void
    {
        if ($comboItems === []) {
            return;
        }

        $product->comboItems()->delete();

        foreach ($comboItems as $index => $item) {
            ProductComboItem::query()->create([
                'product_id' => $product->id,
                'included_product_id' => $item['included_product_id'],
                'included_variant_id' => $item['included_variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'is_optional' => $item['is_optional'] ?? false,
                'discount_percentage' => $item['discount_percentage'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Sync Helpers
    // -------------------------------------------------------------------------

    /**
     * Sync gallery images.
     */
    private function syncGallery(Product $product, array $gallery): void
    {
        $product->productImages()->delete();

        foreach ($gallery as $index => $image) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'media_id' => $image['media_id'],
                'sort_order' => $image['sort_order'] ?? $index,
                'alt_text' => $image['alt_text'] ?? null,
                'caption' => $image['caption'] ?? null,
                'is_primary_gallery' => $image['is_primary'] ?? ($index === 0),
            ]);
        }
    }

    /**
     * Sync YouTube videos.
     */
    private function syncVideos(Product $product, array $videos): void
    {
        $product->videos()->delete();

        foreach ($videos as $index => $video) {
            $videoId = $this->extractYouTubeId($video['video_url']);

            if ($videoId === null) {
                continue;
            }

            ProductVideo::query()->create([
                'product_id' => $product->id,
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
     * Extract YouTube video ID.
     */
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

    /**
     * Sync attribute values.
     */
    private function syncAttributeValues(Product $product, array $attributeValues): void
    {
        $product->attributeValues()->delete();

        foreach ($attributeValues as $av) {
            $product->attributeValues()->create([
                'attribute_id' => $av['attribute_id'],
                'attribute_value_id' => $av['attribute_value_id'],
            ]);
        }
    }

    /**
     * Sync product relations.
     */
    private function syncRelations(Product $product, array $relatedIds, string $type): void
    {
        ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('relation_type', $type)
            ->delete();

        foreach ($relatedIds as $index => $relatedId) {
            ProductRelation::query()->create([
                'product_id' => $product->id,
                'related_product_id' => $relatedId,
                'relation_type' => $type,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Sync pricing tiers.
     */
    private function syncPricingTiers(Product $product, array $tiers): void
    {
        $product->pricingTiers()->whereNull('variant_id')->delete();

        foreach ($tiers as $tier) {
            $tier['product_id'] = $product->id;
            unset($tier['id']);
            ProductPricingTier::query()->create($tier);
        }
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
            $ids = $ids->prepend((int) $primaryCategoryId)->unique()->values();
        }

        if ($ids->isEmpty() && $product->category_id) {
            $ids = collect([(int) $product->category_id]);
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

        $primaryId = $primaryCategoryId
            ? (int) $primaryCategoryId
            : $ids->first();

        if ($primaryId) {
            $product->forceFill(['category_id' => $primaryId])->saveQuietly();
        }
    }

    /**
     * Keep status and visibility flags consistent.
     *
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
                $data['is_visible'] = $status === ProductStatus::Active
                    ? ($data['is_visible'] ?? true)
                    : false;

                if ($status === ProductStatus::Active && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }
            }
        } elseif (array_key_exists('is_visible', $data)) {
            $data['status'] = $data['is_visible']
                ? ProductStatus::Active->value
                : ProductStatus::Draft->value;
        }

        return $data;
    }

    /**
     * @return array{total: int, draft: int, active: int, archived: int, featured: int, low_stock: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Product::query()->count(),
            'draft' => Product::query()->where('status', ProductStatus::Draft)->count(),
            'active' => Product::query()->where('status', ProductStatus::Active)->count(),
            'archived' => Product::query()->where('status', ProductStatus::Archived)->count(),
            'featured' => Product::query()->where('is_featured', true)->count(),
            'low_stock' => Product::query()->lowStock()->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Product::query()
            ->where('status', ProductStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (Product $product): array => [
                'label' => "{$product->name} ({$product->sku})",
                'value' => $product->id,
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
            ->with(['category', 'brand', 'inventory'])
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
}
