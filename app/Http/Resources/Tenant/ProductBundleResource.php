<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductBundle;
use App\Models\Tenant\ProductImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductBundle
 */
class ProductBundleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'included_product_id' => $this->included_product_id,
            'included_variant_id' => $this->included_variant_id,
            'quantity' => $this->quantity,
            'is_optional' => $this->is_optional,
            'discount_percentage' => $this->discount_percentage,
            'fixed_price' => $this->fixed_price,
            'sort_order' => $this->sort_order,
            'included_product' => $this->when(
                $this->relationLoaded('includedProduct') && $this->includedProduct,
                fn () => [
                    'id' => $this->includedProduct->id,
                    'name' => $this->includedProduct->name,
                    'slug' => $this->includedProduct->slug,
                    'sku' => $this->includedProduct->defaultVariant?->sku,
                    'primary_image_media' => $this->includedProduct->relationLoaded('images')
                        ? $this->formatPrimaryImage($this->includedProduct->images)
                        : null,
                ],
            ),
            'included_variant' => new ProductVariantResource($this->whenLoaded('includedVariant')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return array<string, mixed>|null
     */
    private function formatPrimaryImage($images): ?array
    {
        $image = $images->firstWhere('is_primary', true) ?? $images->first();

        if (! $image || ! $image->relationLoaded('media') || ! $image->media) {
            return null;
        }

        return [
            'id' => $image->media->id,
            'url' => $image->media->getUrl(),
            'name' => $image->media->name,
        ];
    }
}
