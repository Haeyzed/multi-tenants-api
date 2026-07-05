<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Supplier;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuppliersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Supplier
    {
        return new Supplier([
            'name' => (string) $row['name'],
            'code' => (string) $row['code'],
            'description' => $row['description'] ?? null,
            'contact_name' => $row['contact_name'] ?? null,
            'contact_email' => $row['contact_email'] ?? null,
            'contact_phone' => $row['contact_phone'] ?? null,
            'website_url' => $row['website_url'] ?? null,
            'tax_id' => $row['tax_id'] ?? null,
            'registration_number' => $row['registration_number'] ?? null,
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
            '*.code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
            '*.description' => ['nullable', 'string'],
            '*.contact_name' => ['nullable', 'string', 'max:255'],
            '*.contact_email' => ['nullable', 'email', 'max:255'],
            '*.contact_phone' => ['nullable', 'string', 'max:30'],
            '*.website_url' => ['nullable', 'url', 'max:255'],
            '*.tax_id' => ['nullable', 'string', 'max:255'],
            '*.registration_number' => ['nullable', 'string', 'max:255'],
            '*.is_active' => ['nullable'],
        ];
    }
}
