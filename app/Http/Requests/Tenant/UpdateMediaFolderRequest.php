<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates media library folder update requests.
 */
class UpdateMediaFolderRequest extends FormRequest
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
        $folderId = $this->route('folder')?->id ?? $this->route('folder');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('media_folders', 'id')->whereNot('id', $folderId),
            ],
        ];
    }
}
