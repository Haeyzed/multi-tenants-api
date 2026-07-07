<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Supplier
 */
class SupplierResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'website_url' => $this->website_url,
            'tax_id' => $this->tax_id,
            'registration_number' => $this->registration_number,
            'is_active' => $this->is_active,
            'products_count' => $this->when(
                isset($this->products_count),
                fn() => $this->products_count,
            ),
            'addresses' => SupplierAddressResource::collection($this->whenLoaded('addresses')),
            'bank_accounts' => SupplierBankAccountResource::collection($this->whenLoaded('bankAccounts')),
            'contacts' => SupplierContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
