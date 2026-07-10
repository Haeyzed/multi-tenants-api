<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\UnitConversionOperator;
use App\Enums\Tenant\UnitType;
use App\Models\Tenant\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('units', 'code')->ignore($unit->id)],
            'symbol' => ['sometimes', 'string', 'max:20'],
            'type' => ['sometimes', 'string', Rule::in(UnitType::values())],
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
