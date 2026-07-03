<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CustomerGroup
 */
class CustomerGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'discount_percent' => $this->discount_percent,
            'discount_percentage' => $this->discount_percent,
            'is_active' => $this->is_active,
            'customers_count' => $this->whenCounted('customers'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
