<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Customer;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Customer
    {
        return new Customer([
            'first_name' => (string) $row['first_name'],
            'last_name' => (string) $row['last_name'],
            'email' => filled($row['email'] ?? null) ? (string) $row['email'] : null,
            'phone' => filled($row['phone'] ?? null) ? (string) $row['phone'] : null,
            'customer_group_id' => filled($row['customer_group_id'] ?? null) ? (int) $row['customer_group_id'] : null,
            'date_of_birth' => filled($row['date_of_birth'] ?? null) ? (string) $row['date_of_birth'] : null,
            'gender' => filled($row['gender'] ?? null) ? (string) $row['gender'] : null,
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.first_name' => ['required', 'string', 'max:255'],
            '*.last_name' => ['required', 'string', 'max:255'],
            '*.email' => ['nullable', 'email', 'max:255'],
            '*.phone' => ['nullable', 'string', 'max:30'],
            '*.customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id'],
            '*.date_of_birth' => ['nullable', 'date'],
            '*.gender' => ['nullable', 'string', 'max:20'],
            '*.is_active' => ['nullable'],
        ];
    }
}
