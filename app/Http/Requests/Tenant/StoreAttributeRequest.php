<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\AttributeDisplayType;
use App\Enums\Tenant\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'slug')],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'code')],
            'type' => ['sometimes', 'string', Rule::in(AttributeType::values())],
            'display_type' => ['sometimes', 'string', Rule::in(AttributeDisplayType::values())],
            'description' => ['nullable', 'string'],
            'is_filterable' => ['sometimes', 'boolean'],
            'is_visible_on_product' => ['sometimes', 'boolean'],
            'is_visible_on_listing' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'is_variant' => ['sometimes', 'boolean'],
            'is_user_defined' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'validation_rules' => ['nullable', 'array'],
            'default_value' => ['nullable', 'array'],
        ];
    }
}
