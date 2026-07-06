<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates variant generation from product options.
 */
class GenerateProductVariantsRequest extends FormRequest
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
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'inventory' => ['nullable', 'array'],
            'inventory.warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')],
            'inventory.quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.reorder_level' => ['nullable', 'integer', 'min:0'],
            'skip_existing' => ['sometimes', 'boolean'],
        ];
    }
}
