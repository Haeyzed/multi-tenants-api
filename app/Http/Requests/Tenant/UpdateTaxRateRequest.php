<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRateRequest extends FormRequest
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
            'tax_class_id' => ['sometimes', 'integer', 'exists:tax_classes,id'],
            'tax_zone_id' => ['sometimes', 'integer', 'exists:tax_zones,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'priority' => ['sometimes', 'integer', 'min:1'],
            'is_compound' => ['sometimes', 'boolean'],
            'applies_to_shipping' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
