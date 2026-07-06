<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\AttributeDisplayType;
use App\Enums\Tenant\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
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
        $attributeId = $this->route('attribute')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($attributeId)],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('attributes', 'code')->ignore($attributeId)],
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
