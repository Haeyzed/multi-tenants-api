<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Enums\Tenant\UnitType;
use App\Models\Tenant\Unit;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UnitsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Unit
    {
        return new Unit([
            'name' => (string) $row['name'],
            'code' => (string) $row['code'],
            'symbol' => (string) $row['symbol'],
            'type' => (string) $row['type'],
            'conversion_factor' => filled($row['conversion_factor'] ?? null)
                ? (float) $row['conversion_factor']
                : 1,
            'is_base' => filter_var($row['is_base'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:units,code'],
            '*.symbol' => ['required', 'string', 'max:20'],
            '*.type' => ['required', 'string', Rule::in(UnitType::values())],
            '*.conversion_factor' => ['nullable', 'numeric', 'gt:0'],
            '*.is_base' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
