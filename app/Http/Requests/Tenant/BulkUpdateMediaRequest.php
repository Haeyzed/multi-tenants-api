<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates bulk media metadata update requests.
 */
class BulkUpdateMediaRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('media', 'id')],
            'title' => ['sometimes', 'string', 'max:255'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
