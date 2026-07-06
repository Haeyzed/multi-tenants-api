<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Enums\Tenant\AttributeDisplayType;
use App\Enums\Tenant\AttributeType;
use App\Models\Tenant\Attribute;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttributesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Attribute
    {
        $name = (string) $row['name'];
        $slug = filled($row['slug'] ?? null) ? (string) $row['slug'] : Str::slug($name);
        $code = filled($row['code'] ?? null) ? (string) $row['code'] : Str::upper(Str::slug($slug, '_'));

        $attributes = [
            'name' => $name,
            'slug' => $slug,
            'code' => $code,
            'type' => filled($row['type'] ?? null) ? (string) $row['type'] : AttributeType::Select->value,
            'display_type' => filled($row['display_type'] ?? null) ? (string) $row['display_type'] : AttributeDisplayType::Dropdown->value,
            'is_filterable' => filter_var($row['is_filterable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_variant' => filter_var($row['is_variant'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_required' => filter_var($row['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
        ];

        $lookup = filled($row['slug'] ?? null)
            ? ['slug' => (string) $row['slug']]
            : ['name' => $name];

        return Attribute::updateOrCreate($lookup, $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            '*.code' => ['nullable', 'string', 'max:255'],
            '*.type' => ['nullable', 'string', Rule::in(AttributeType::values())],
            '*.display_type' => ['nullable', 'string', Rule::in(AttributeDisplayType::values())],
            '*.is_filterable' => ['nullable'],
            '*.is_variant' => ['nullable'],
            '*.is_required' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
