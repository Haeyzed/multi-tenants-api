<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultVariant = $this->resolveDefaultVariant();
        $images = $this->resolveProductImages();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'summary' => $this->summary,
            'description' => $this->description,

            'type' => [
                'value' => $this->type?->value ?? $this->type,
                'label' => $this->type?->label(),
                'description' => $this->type?->description(),
                'requires_shipping' => $this->type?->requiresShipping() ?? true,
                'tracks_inventory' => $this->type?->tracksInventory() ?? true,
            ],
            'condition' => [
                'value' => $this->condition?->value ?? $this->condition,
                'label' => $this->condition?->label(),
            ],
            'status' => [
                'value' => $this->status?->value ?? $this->status,
                'label' => $this->status?->label(),
            ],
            'visibility' => [
                'value' => $this->visibility?->value ?? $this->visibility,
                'label' => $this->visibility?->label(),
            ],

            'brand_id' => $this->brand_id,
            'attribute_set_id' => $this->attribute_set_id,
            'tax_class_id' => $this->tax_class_id,
            'is_featured' => $this->is_featured,
            'is_returnable' => $this->is_returnable,
            'return_period_days' => $this->return_period_days,
            'warranty_period_months' => $this->warranty_period_months,
            'min_order_quantity' => $this->min_order_quantity,
            'max_order_quantity' => $this->max_order_quantity,
            'track_inventory' => $this->track_inventory,
            'allow_backorders' => $this->allow_backorders,
            'requires_shipping' => $this->requires_shipping,
            'is_taxable' => $this->is_taxable,
            'published_at' => $this->published_at?->toIso8601String(),
            'discontinued_at' => $this->discontinued_at?->toIso8601String(),
            'is_published' => $this->isPublished(),

            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'search_keywords' => $this->search_keywords,

            'price' => $defaultVariant?->price,
            'compare_at_price' => $defaultVariant?->compare_at_price,
            'selling_price' => $defaultVariant?->sellingPrice(),
            'is_on_sale' => $defaultVariant?->isOnSale(),
            'discount_percentage' => $defaultVariant?->discountPercentage(),
            'sku' => $defaultVariant?->sku,

            'brand' => new BrandResource($this->whenLoaded('brand')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'primary_category_id' => $this->whenLoaded(
                'categories',
                fn () => $this->categories->firstWhere('pivot.is_primary', true)?->id,
            ),
            'category_ids' => $this->whenLoaded(
                'categories',
                fn () => $this->categories->pluck('id')->values(),
            ),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'labels' => ProductLabelResource::collection($this->whenLoaded('labels')),
            'label_ids' => $this->whenLoaded(
                'labels',
                fn () => $this->labels->pluck('id')->values(),
            ),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'collection_ids' => $this->whenLoaded(
                'collections',
                fn () => $this->collections->pluck('id')->values(),
            ),
            'suppliers' => $this->whenLoaded('productSuppliers', function () {
                return $this->productSuppliers->map(fn ($assignment) => [
                    'id' => $assignment->id,
                    'supplier_id' => $assignment->supplier_id,
                    'supplier_sku' => $assignment->supplier_sku,
                    'supplier_cost' => $assignment->supplier_cost,
                    'lead_time_days' => $assignment->lead_time_days,
                    'minimum_quantity' => $assignment->minimum_quantity,
                    'is_primary' => $assignment->is_primary,
                    'supplier' => $assignment->relationLoaded('supplier') && $assignment->supplier
                        ? [
                            'id' => $assignment->supplier->id,
                            'name' => $assignment->supplier->name,
                            'code' => $assignment->supplier->code,
                        ]
                        : null,
                ]);
            }),
            'primary_supplier_id' => $this->whenLoaded(
                'productSuppliers',
                fn () => $this->productSuppliers->firstWhere('is_primary', true)?->supplier_id,
            ),

            'images' => $this->when(
                $this->imagesAreLoaded(),
                fn () => $this->formatProductImages($images),
            ),
            'gallery' => $this->when(
                $this->imagesAreLoaded(),
                fn () => $this->formatProductGallery($images),
            ),
            'primary_image_media' => $this->when(
                $this->imagesAreLoaded(),
                fn () => $this->formatPrimaryImageMedia($images),
            ),

            'videos' => $this->whenLoaded('videos', function () {
                return $this->videos->map(fn ($video) => [
                    'id' => $video->id,
                    'video_id' => $video->video_id,
                    'video_url' => $video->video_url,
                    'embed_url' => $video->embedUrl(),
                    'thumbnail_url' => $video->thumbnailUrl(),
                    'watch_url' => $video->watchUrl(),
                    'title' => $video->title,
                    'description' => $video->description,
                    'sort_order' => $video->sort_order,
                    'is_primary' => $video->is_primary,
                ]);
            }),

            'downloads' => ProductDownloadResource::collection($this->whenLoaded('downloads')),
            'bundle_items' => ProductBundleResource::collection($this->whenLoaded('bundleItems')),
            'service' => new ProductServiceResource($this->whenLoaded('service')),
            'subscription' => new ProductSubscriptionResource($this->whenLoaded('subscription')),
            'providers' => ProductProviderResource::collection($this->whenLoaded('providers')),

            'variants_count' => $this->whenCounted('variants'),
            'reviews_count' => $this->whenCounted('reviews'),
            'default_variant' => new ProductVariantResource($this->when(
                $this->relationLoaded('defaultVariant') && $this->defaultVariant,
                $this->defaultVariant,
            )),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'options' => ProductOptionResource::collection($this->whenLoaded('options')),

            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(fn ($attributeValue) => [
                    'attribute' => [
                        'id' => $attributeValue->attribute->id,
                        'name' => $attributeValue->attribute->name,
                        'slug' => $attributeValue->attribute->slug,
                    ],
                    'value' => [
                        'id' => $attributeValue->attributeValue?->id,
                        'value' => $attributeValue->display_value,
                        'slug' => $attributeValue->attributeValue?->slug,
                    ],
                ]);
            }),

            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => $review->content,
                    'author_name' => $review->author_name,
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'is_approved' => $review->is_approved,
                    'created_at' => $review->created_at?->toIso8601String(),
                ]);
            }),

            'related_products' => ProductResource::collection(
                $this->whenLoaded('relatedProducts', fn () => $this->relatedProducts->pluck('relatedProduct'))
            ),
            'cross_sell_products' => ProductResource::collection(
                $this->whenLoaded('crossSellProducts', fn () => $this->crossSellProducts->pluck('relatedProduct'))
            ),
            'up_sell_products' => ProductResource::collection(
                $this->whenLoaded('upSellProducts', fn () => $this->upSellProducts->pluck('relatedProduct'))
            ),

            'seo' => $this->whenLoaded('seo', fn () => [
                'canonical_url' => $this->seo->canonical_url,
                'og_title' => $this->seo->og_title,
                'og_description' => $this->seo->og_description,
                'og_image_media_id' => $this->seo->og_image_media_id,
                'og_image' => $this->when($this->seo->ogImageMedia, fn () => [
                    'id' => $this->seo->ogImageMedia->id,
                    'url' => $this->seo->ogImageMedia->getUrl(),
                    'name' => $this->seo->ogImageMedia->name,
                ]),
                'twitter_card' => $this->seo->twitter_card,
                'twitter_title' => $this->seo->twitter_title,
                'twitter_description' => $this->seo->twitter_description,
                'twitter_image_media_id' => $this->seo->twitter_image_media_id,
                'twitter_image' => $this->when($this->seo->twitterImageMedia, fn () => [
                    'id' => $this->seo->twitterImageMedia->id,
                    'url' => $this->seo->twitterImageMedia->getUrl(),
                    'name' => $this->seo->twitterImageMedia->name,
                ]),
                'robots_meta' => $this->seo->robots_meta,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolveDefaultVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('defaultVariant') && $this->defaultVariant) {
            return $this->defaultVariant;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
        }

        return null;
    }

    /**
     * @return Collection<int, ProductImage>
     */
    private function resolveProductImages(): Collection
    {
        if ($this->relationLoaded('productImages')) {
            return $this->productImages;
        }

        if ($this->relationLoaded('images')) {
            return $this->images;
        }

        return collect();
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return list<array<string, mixed>>
     */
    private function formatProductImages(Collection $images): array
    {
        if ($images->isEmpty()) {
            return [];
        }

        return $images->map(fn (ProductImage $image): array => [
            'id' => $image->id,
            'media_id' => $image->media_id,
            'sort_order' => $image->sort_order,
            'alt_text' => $image->alt_text,
            'caption' => $image->caption,
            'is_primary' => $image->is_primary,
            'product_variant_id' => $image->product_variant_id,
            'media' => $image->relationLoaded('media') && $image->media
                ? [
                    'id' => $image->media->id,
                    'file_name' => $image->media->file_name,
                    'name' => $image->media->name,
                    'url' => $image->media->getUrl(),
                ]
                : null,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return list<array<string, mixed>>
     */
    private function formatProductGallery(Collection $images): array
    {
        if ($images->isEmpty()) {
            return [];
        }

        return $images
            ->sortBy('sort_order')
            ->values()
            ->map(fn (ProductImage $image): array => [
                'id' => $image->id,
                'media_id' => $image->media_id,
                'sort_order' => $image->sort_order,
                'alt_text' => $image->alt_text,
                'caption' => $image->caption,
                'is_primary' => $image->is_primary,
                'media' => $image->relationLoaded('media') && $image->media
                    ? [
                        'id' => $image->media->id,
                        'file_name' => $image->media->file_name,
                        'name' => $image->media->name,
                        'url' => $image->media->getUrl(),
                    ]
                    : null,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return array<string, mixed>|null
     */
    private function formatPrimaryImageMedia(Collection $images): ?array
    {
        if ($images->isEmpty()) {
            return null;
        }

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();

        if (! $primary || ! $primary->relationLoaded('media') || ! $primary->media) {
            return null;
        }

        return [
            'id' => $primary->media->id,
            'file_name' => $primary->media->file_name,
            'name' => $primary->media->name,
            'url' => $primary->media->getUrl(),
        ];
    }

    private function imagesAreLoaded(): bool
    {
        return $this->relationLoaded('productImages') || $this->relationLoaded('images');
    }
}
