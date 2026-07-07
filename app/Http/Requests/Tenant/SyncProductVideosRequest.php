<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates product video sync requests.
 */
class SyncProductVideosRequest extends FormRequest
{
    use PreparesProductCatalogRequest;

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
            'videos' => ['required', 'array'],
            'videos.*.video_url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)[a-zA-Z0-9_-]{11}.*$/'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.description' => ['nullable', 'string', 'max:1000'],
            'videos.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'videos.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCatalogForValidation();
    }
}
