<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product document update requests.
 */
class UpdateProductDocumentRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'description' => ['nullable', 'string'],
            'document_type' => ['sometimes', 'string', 'max:50', Rule::in(['manual', 'datasheet', 'certificate', 'warranty'])],
            'language' => ['sometimes', 'string', 'max:5'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
