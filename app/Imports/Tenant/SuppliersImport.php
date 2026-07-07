<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Imports\Concerns\NormalizesImportRows;
use App\Imports\Concerns\TracksImportResults;
use App\Models\Tenant\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuppliersImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use NormalizesImportRows, SkipsFailures, TracksImportResults;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = (string) $row['code'];

            $attributes = [
                'name' => (string) $row['name'],
                'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
                'contact_name' => filled($row['contact_name'] ?? null) ? (string) $row['contact_name'] : null,
                'contact_email' => filled($row['contact_email'] ?? null) ? (string) $row['contact_email'] : null,
                'contact_phone' => $this->stringify($row['contact_phone'] ?? null),
                'website_url' => filled($row['website_url'] ?? null) ? (string) $row['website_url'] : null,
                'tax_id' => filled($row['tax_id'] ?? null) ? (string) $row['tax_id'] : null,
                'registration_number' => filled($row['registration_number'] ?? null)
                    ? (string) $row['registration_number']
                    : null,
                'is_active' => $this->parseBoolean($row['is_active'] ?? true),
            ];

            $existing = Supplier::withTrashed()->where('code', $code)->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            Supplier::query()->updateOrCreate(['code' => $code], $attributes);

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
            'code',
            'contact_phone',
            'tax_id',
            'registration_number',
        ]);

        return $this->nullifyEmpty($data, [
            'description',
            'contact_name',
            'contact_email',
            'contact_phone',
            'website_url',
            'tax_id',
            'registration_number',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.code' => ['required', 'string', 'max:50'],
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
