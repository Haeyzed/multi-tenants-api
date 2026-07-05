<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\TaxClass;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TaxClassesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): TaxClass
    {
        return new TaxClass([
            'name' => (string) $row['name'],
            'code' => (string) $row['code'],
            'description' => $row['description'] ?? null,
            'is_default' => filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
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
            '*.code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:tax_classes,code'],
            '*.description' => ['nullable', 'string'],
            '*.is_default' => ['nullable'],
            '*.is_active' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
