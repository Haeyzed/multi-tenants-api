<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Inventory
 */
class InventoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'warehouse_id' => $this->warehouse_id,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'product' => $this->whenLoaded('variant', fn () => $this->variant->relationLoaded('product') && $this->variant->product
                ? [
                    'id' => $this->variant->product->id,
                    'name' => $this->variant->product->name,
                    'slug' => $this->variant->product->slug,
                ]
                : null),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'incoming_quantity' => $this->incoming_quantity,
            'damaged_quantity' => $this->damaged_quantity,
            'available_quantity' => $this->availableQuantity(),
            'reorder_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'location_code' => $this->location_code,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_low_stock' => $this->isLowStock(),
        ];
    }
}
