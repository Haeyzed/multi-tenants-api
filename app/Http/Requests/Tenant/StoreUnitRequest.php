<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\UnitConversionOperator;
use App\Enums\Tenant\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')],
            'symbol' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', Rule::in(UnitType::values())],
            'conversion_factor' => ['sometimes', 'numeric', 'gt:0'],
            'conversion_operator' => [
                'nullable',
                'string',
                Rule::enum(UnitConversionOperator::class),
                'required_with:conversion_value',
            ],
            'conversion_value' => ['nullable', 'numeric', 'gt:0', 'required_with:conversion_operator'],
            'is_base' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
