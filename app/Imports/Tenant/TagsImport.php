<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Tag;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TagsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Tag
    {
        $name = (string) $row['name'];

        $attributes = [
            'name' => $name,
            'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : null,
            'color' => $row['color'] ?? null,
            'icon' => $row['icon'] ?? null,
            'is_visible' => filter_var($row['is_visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
        ];

        $lookup = filled($row['slug'] ?? null)
            ? ['slug' => (string) $row['slug']]
            : ['name' => $name];

        return Tag::updateOrCreate($lookup, $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            '*.color' => ['nullable', 'string', 'max:50'],
            '*.icon' => ['nullable', 'string', 'max:100'],
            '*.is_visible' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
