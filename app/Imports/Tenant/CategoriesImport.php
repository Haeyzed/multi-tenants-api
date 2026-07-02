<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Category;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CategoriesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Category
    {
        return new Category([
            'name' => (string) $row['name'],
            'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : null,
            'description' => $row['description'] ?? null,
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'parent_id' => filled($row['parent_id'] ?? null) ? (int) $row['parent_id'] : null,
            'is_visible' => filter_var($row['is_visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'image_media_id' => filled($row['image_media_id'] ?? null) ? (int) $row['image_media_id'] : null,
            'banner_media_id' => filled($row['banner_media_id'] ?? null) ? (int) $row['banner_media_id'] : null,
            'color' => $row['color'] ?? null,
            'icon' => $row['icon'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:categories,slug'],
            '*.description' => ['nullable', 'string'],
            '*.meta_title' => ['nullable', 'string', 'max:255'],
            '*.meta_description' => ['nullable', 'string'],
            '*.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            '*.is_visible' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
            '*.image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            '*.banner_media_id' => ['nullable', 'integer', 'exists:media,id'],
            '*.color' => ['nullable', 'string', 'max:50'],
            '*.icon' => ['nullable', 'string', 'max:100'],
        ];
    }
}
