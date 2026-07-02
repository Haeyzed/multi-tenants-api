<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Brand;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BrandsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Brand
    {
        return new Brand([
            'name' => (string) $row['name'],
            'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : null,
            'description' => $row['description'] ?? null,
            'is_visible' => filter_var($row['is_visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'logo_media_id' => filled($row['logo_media_id'] ?? null) ? (int) $row['logo_media_id'] : null,
            'banner_media_id' => filled($row['banner_media_id'] ?? null) ? (int) $row['banner_media_id'] : null,
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'website_url' => $row['website_url'] ?? null,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:brands,slug'],
            '*.description' => ['nullable', 'string'],
            '*.is_visible' => ['nullable'],
            '*.logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            '*.banner_media_id' => ['nullable', 'integer', 'exists:media,id'],
            '*.meta_title' => ['nullable', 'string', 'max:255'],
            '*.meta_description' => ['nullable', 'string'],
            '*.website_url' => ['nullable', 'url', 'max:255'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
