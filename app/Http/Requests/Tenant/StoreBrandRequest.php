<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates brand creation requests.
 */
class StoreBrandRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'logo_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'banner_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'country_of_origin' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
