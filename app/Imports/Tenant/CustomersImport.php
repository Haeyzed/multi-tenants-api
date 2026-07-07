<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Imports\Concerns\NormalizesImportRows;
use App\Imports\Concerns\TracksImportResults;
use App\Models\Tenant\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomersImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use NormalizesImportRows, SkipsFailures, TracksImportResults;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = filled($row['email'] ?? null) ? strtolower((string) $row['email']) : null;

            $attributes = [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'email' => $email,
                'phone' => $this->stringify($row['phone'] ?? null),
                'customer_group_id' => filled($row['customer_group_id'] ?? null)
                    ? (int) $row['customer_group_id']
                    : null,
                'date_of_birth' => filled($row['date_of_birth'] ?? null)
                    ? (string) $row['date_of_birth']
                    : null,
                'gender' => filled($row['gender'] ?? null) ? (string) $row['gender'] : null,
                'is_active' => $this->parseBoolean($row['is_active'] ?? true),
            ];

            if ($email !== null) {
                $existing = Customer::withTrashed()->where('email', $email)->first();

                if ($existing?->trashed()) {
                    $existing->restore();
                }

                Customer::query()->updateOrCreate(['email' => $email], $attributes);
            } else {
                Customer::query()->create($attributes);
            }

            $this->incrementImported();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index): array
    {
        $data = $this->stringifyFields($data, [
            'phone',
        ]);

        if (array_key_exists('customer_group_id', $data) && $data['customer_group_id'] === '') {
            $data['customer_group_id'] = null;
        }

        return $this->nullifyEmpty($data, [
            'email',
            'phone',
            'customer_group_id',
            'date_of_birth',
            'gender',
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
