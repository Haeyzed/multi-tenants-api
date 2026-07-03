<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\CustomerGroup;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerGroupsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): CustomerGroup
    {
        return new CustomerGroup([
            'name' => (string) $row['name'],
            'description' => $row['description'] ?? null,
            'discount_percent' => filled($row['discount_percent'] ?? null) ? (float) $row['discount_percent'] : null,
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.description' => ['nullable', 'string'],
            '*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            '*.is_active' => ['nullable'],
        ];
    }
}
