<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WarehouseLocation
 */
class WarehouseLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'zone_id' => $this->zone_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'max_weight' => $this->max_weight,
            'max_volume' => $this->max_volume,
            'is_active' => $this->is_active,
            'is_picking_location' => $this->is_picking_location,
            'zone' => $this->whenLoaded('zone', fn () => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
                'code' => $this->zone->code,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
