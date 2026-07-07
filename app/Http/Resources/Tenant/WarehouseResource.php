<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Warehouse
 */
class WarehouseResource extends JsonResource
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
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_name' => $this->manager_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
            'zones_count' => $this->when(isset($this->zones_count), fn() => $this->zones_count),
            'locations_count' => $this->when(isset($this->locations_count), fn() => $this->locations_count),
            'inventories_count' => $this->when(isset($this->inventories_count), fn() => $this->inventories_count),
            'zones' => WarehouseZoneResource::collection($this->whenLoaded('zones')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
