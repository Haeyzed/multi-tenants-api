<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\InventoryTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryTransfer
 */
class InventoryTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'shipping_cost' => $this->shipping_cost,
            'subtotal' => $this->subtotal,
            'grand_total' => $this->grand_total,
            'email_sent' => $this->email_sent,
            'reason' => $this->reason,
            'media_id' => $this->media_id,
            'total_products' => $this->total_products,
            'total_quantity_transferred' => $this->total_quantity_transferred,
            'items_count' => $this->whenCounted('items', fn () => $this->items_count),
            'created_by' => $this->created_by,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'from_warehouse' => $this->whenLoaded('fromWarehouse', fn () => [
                'id' => $this->fromWarehouse->id,
                'name' => $this->fromWarehouse->name,
                'code' => $this->fromWarehouse->code,
            ]),
            'to_warehouse' => $this->whenLoaded('toWarehouse', fn () => [
                'id' => $this->toWarehouse->id,
                'name' => $this->toWarehouse->name,
                'code' => $this->toWarehouse->code,
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
            'items' => InventoryTransferItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
