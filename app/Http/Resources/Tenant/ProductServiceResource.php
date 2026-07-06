<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductService
 */
class ProductServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes_before' => $this->buffer_minutes_before,
            'buffer_minutes_after' => $this->buffer_minutes_after,
            'max_participants' => $this->max_participants,
            'location_type' => $this->location_type,
            'location_address' => $this->location_address,
            'meeting_url' => $this->meeting_url,
            'requires_confirmation' => $this->requires_confirmation,
            'cancellation_hours' => $this->cancellation_hours,
            'instructions' => $this->instructions,
            'total_duration_minutes' => $this->total_duration_minutes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
