<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductStockAlert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductStockAlert
 */
class ProductStockAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'customer_id' => $this->customer_id,
            'email' => $this->email,
            'is_notified' => $this->is_notified,
            'notified_at' => $this->notified_at?->toIso8601String(),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'first_name' => $this->customer?->first_name,
                'last_name' => $this->customer?->last_name,
                'email' => $this->customer?->email,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
