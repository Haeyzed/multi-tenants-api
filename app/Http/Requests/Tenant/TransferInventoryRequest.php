<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates inventory transfer requests.
 */
class TransferInventoryRequest extends FormRequest
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
        $sourceWarehouseId = $this->route('inventory')?->warehouse_id;

        return [
            'destination_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id'),
                Rule::notIn([$sourceWarehouseId]),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
