<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Enums\Tenant\CollectionType;
use App\Models\Tenant\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CollectionsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Collection
    {
        $name = (string) $row['name'];

        $attributes = [
            'name' => $name,
            'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : Str::slug($name),
            'description' => $row['description'] ?? null,
            'is_visible' => filter_var($row['is_visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($row['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'type' => filled($row['type'] ?? null) ? (string) $row['type'] : CollectionType::Manual->value,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
        ];

        $lookup = filled($row['slug'] ?? null)
            ? ['slug' => (string) $row['slug']]
            : ['name' => $name];

        return Collection::updateOrCreate($lookup, $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            '*.description' => ['nullable', 'string'],
            '*.is_visible' => ['nullable'],
            '*.is_featured' => ['nullable'],
            '*.type' => ['nullable', 'string', Rule::in(CollectionType::values())],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
