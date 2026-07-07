<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\TaxClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxClass
 */
class TaxClassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'rates_count' => $this->whenCounted('rates'),
            'products_count' => $this->whenCounted('products'),
            'rates' => $this->whenLoaded('rates', fn() => TaxRateResource::collection($this->rates)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
