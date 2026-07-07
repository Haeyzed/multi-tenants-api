<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TenantUser $user
 * @property Customer $customer
 * @property string $token
 */
class CustomerAuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new TenantUserResource($this->resource['user']),
            'customer' => new CustomerResource($this->resource['customer']),
            'token' => $this->resource['token'],
        ];
    }
}
