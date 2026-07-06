<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeValueRequest extends FormRequest
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
            'value' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'slug')->where('attribute_id', $attributeId),
            ],
            'color_hex' => ['nullable', 'string', 'max:7'],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
