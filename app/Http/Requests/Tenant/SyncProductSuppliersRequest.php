<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product supplier sync requests.
 */
class SyncProductSuppliersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'suppliers' => ['present', 'array'],
            'suppliers.*.supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'suppliers.*.supplier_sku' => ['nullable', 'string', 'max:100'],
            'suppliers.*.supplier_cost' => ['nullable', 'numeric', 'min:0'],
            'suppliers.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'suppliers.*.minimum_quantity' => ['nullable', 'integer', 'min:1'],
            'suppliers.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
