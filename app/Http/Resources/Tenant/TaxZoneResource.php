<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\TaxZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxZone
 */
class TaxZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'state' => $this->state,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'postal_code_pattern' => $this->postal_code_pattern,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_km' => $this->radius_km,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'rates_count' => $this->whenCounted('rates'),
            'rates' => $this->whenLoaded('rates', fn() => TaxRateResource::collection($this->rates)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
