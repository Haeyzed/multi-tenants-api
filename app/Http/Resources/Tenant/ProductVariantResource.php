<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'gtin' => $this->gtin,
            'mpn' => $this->mpn,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'cost_price' => $this->cost_price,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight_unit_id' => $this->weight_unit_id,
            'dimension_unit_id' => $this->dimension_unit_id,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'visibility_label' => $this->visibility?->label(),
            'position' => $this->position,
            'is_default' => $this->is_default,
            'is_on_sale' => $this->isOnSale(),
            'discount_percentage' => $this->discountPercentage(),
            'selling_price' => $this->sellingPrice(),
            'option_values' => $this->when(
                $this->relationLoaded('optionValues') || $this->relationLoaded('variantOptionValues'),
                function () {
                    if ($this->relationLoaded('optionValues')) {
                        return $this->optionValues->map(fn ($optionValue) => [
                            'id' => $optionValue->id,
                            'value' => $optionValue->value,
                            'code' => $optionValue->code,
                            'position' => $optionValue->position,
                            'option' => $this->when($optionValue->relationLoaded('option'), fn () => [
                                'id' => $optionValue->option->id,
                                'name' => $optionValue->option->name,
                                'code' => $optionValue->option->code,
                            ]),
                        ]);
                    }

                    return $this->variantOptionValues->map(fn ($pivot) => [
                        'id' => $pivot->optionValue->id,
                        'value' => $pivot->optionValue->value,
                        'code' => $pivot->optionValue->code,
                        'position' => $pivot->optionValue->position,
                        'option' => $this->when($pivot->relationLoaded('option'), fn () => [
                            'id' => $pivot->option->id,
                            'name' => $pivot->option->name,
                            'code' => $pivot->option->code,
                        ]),
                    ]);
                },
            ),
            'inventories' => InventoryResource::collection($this->whenLoaded('inventories')),
            'price_tiers' => ProductPriceTierResource::collection($this->whenLoaded('priceTiers')),
            'image_media_id' => $this->image_media_id,
            'image_media' => $this->whenLoaded('imageMedia', fn () => $this->imageMedia ? [
                'id' => $this->imageMedia->id,
                'file_name' => $this->imageMedia->file_name,
                'name' => $this->imageMedia->name,
                'mime_type' => $this->imageMedia->mime_type,
                'url' => $this->imageMedia->getUrl(),
            ] : null),
            'image' => $this->whenLoaded('imageMedia', fn () => $this->imageMedia ? [
                'id' => $this->imageMedia->id,
                'file_name' => $this->imageMedia->file_name,
                'mime_type' => $this->imageMedia->mime_type,
                'url' => $this->imageMedia->getUrl(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
