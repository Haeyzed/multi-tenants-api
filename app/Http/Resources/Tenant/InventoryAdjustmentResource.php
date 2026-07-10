<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\InventoryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryAdjustment
 */
class InventoryAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_number' => $this->adjustment_number,
            'warehouse_id' => $this->warehouse_id,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'reference_number' => $this->reference_number,
            'reason' => $this->reason,
            'media_id' => $this->media_id,
            'total_products' => $this->total_products,
            'total_quantity_adjusted' => $this->total_quantity_adjusted,
            'items_count' => $this->whenCounted('items', fn () => $this->items_count),
            'created_by' => $this->created_by,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null),
            'media' => $this->whenLoaded('media', fn () => $this->media ? [
                'id' => $this->media->id,
                'file_name' => $this->media->file_name,
                'name' => $this->media->name,
                'mime_type' => $this->media->mime_type,
                'url' => $this->media->getUrl(),
            ] : null),
            'items' => InventoryAdjustmentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
