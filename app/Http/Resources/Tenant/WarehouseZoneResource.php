<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WarehouseZone
 */
class WarehouseZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'zone_type' => $this->zone_type,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'locations_count' => $this->when(isset($this->locations_count), fn() => $this->locations_count),
            'locations' => WarehouseLocationResource::collection($this->whenLoaded('locations')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
