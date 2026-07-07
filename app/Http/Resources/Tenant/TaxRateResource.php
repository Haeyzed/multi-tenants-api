<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxRate
 */
class TaxRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_class_id' => $this->tax_class_id,
            'tax_zone_id' => $this->tax_zone_id,
            'name' => $this->name,
            'rate' => $this->rate,
            'priority' => $this->priority,
            'is_compound' => $this->is_compound,
            'applies_to_shipping' => $this->applies_to_shipping,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'is_active' => $this->is_active,
            'tax_class' => $this->whenLoaded('taxClass', fn() => new TaxClassResource($this->taxClass)),
            'tax_zone' => $this->whenLoaded('taxZone', fn() => new TaxZoneResource($this->taxZone)),
            'rules' => $this->whenLoaded('rules', fn() => TaxRuleResource::collection($this->rules)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
