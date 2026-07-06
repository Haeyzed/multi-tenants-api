<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductProvider
 */
class ProductProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'provider_id' => $this->provider_id,
            'is_primary' => $this->is_primary,
            'commission_rate' => $this->commission_rate,
            'provider' => $this->when(
                $this->relationLoaded('provider') && $this->provider,
                fn () => [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'email' => $this->provider->email,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
