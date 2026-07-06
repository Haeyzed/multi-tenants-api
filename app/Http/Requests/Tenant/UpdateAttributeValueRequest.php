<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeValueRequest extends FormRequest
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
        $valueId = $this->route('value')?->id;

        return [
            'value' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'slug')
                    ->where('attribute_id', $attributeId)
                    ->ignore($valueId),
            ],
            'color_hex' => ['nullable', 'string', 'max:7'],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
