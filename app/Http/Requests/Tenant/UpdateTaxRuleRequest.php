<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxRuleRequest extends FormRequest
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
            'tax_rate_id' => ['sometimes', 'integer', 'exists:tax_rates,id'],
            'applicable_type' => ['sometimes', 'string', Rule::in(['product', 'customer_group'])],
            'applicable_id' => ['sometimes', 'integer', 'min:1'],
            'rule_type' => ['sometimes', 'string', Rule::in(['override', 'exempt', 'reduce', 'increase'])],
            'adjustment_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
