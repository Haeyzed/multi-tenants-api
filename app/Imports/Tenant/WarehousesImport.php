<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Imports\Concerns\NormalizesImportRows;
use App\Imports\Concerns\TracksImportResults;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WarehousesImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use NormalizesImportRows, SkipsFailures, TracksImportResults;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = (string) $row['code'];
            $isPrimary = $this->parseBoolean($row['is_primary'] ?? false, false);

            if ($isPrimary) {
                Warehouse::query()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $attributes = [
                'name' => (string) $row['name'],
                'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
                'address_line_1' => filled($row['address_line_1'] ?? null) ? (string) $row['address_line_1'] : null,
                'address_line_2' => filled($row['address_line_2'] ?? null) ? (string) $row['address_line_2'] : null,
                'city' => filled($row['city'] ?? null) ? (string) $row['city'] : null,
                'state' => filled($row['state'] ?? null) ? (string) $row['state'] : null,
                'postal_code' => $this->stringify($row['postal_code'] ?? null),
                'country' => filled($row['country'] ?? null) ? strtoupper((string) $row['country']) : null,
                'phone' => $this->stringify($row['phone'] ?? null),
                'email' => filled($row['email'] ?? null) ? (string) $row['email'] : null,
                'manager_name' => filled($row['manager_name'] ?? null) ? (string) $row['manager_name'] : null,
                'latitude' => filled($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
                'longitude' => filled($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
                'is_active' => $this->parseBoolean($row['is_active'] ?? true),
                'is_primary' => $isPrimary,
                'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
            ];

            $existing = Warehouse::withTrashed()->where('code', $code)->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            Warehouse::query()->updateOrCreate(['code' => $code], $attributes);

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
            'postal_code',
            'phone',
            'country',
        ]);

        return $this->nullifyEmpty($data, [
            'description',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'email',
            'manager_name',
            'latitude',
            'longitude',
            'sort_order',
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
            '*.address_line_1' => ['nullable', 'string', 'max:255'],
            '*.address_line_2' => ['nullable', 'string', 'max:255'],
            '*.city' => ['nullable', 'string', 'max:255'],
            '*.state' => ['nullable', 'string', 'max:255'],
            '*.postal_code' => ['nullable', 'string', 'max:20'],
            '*.country' => ['nullable', 'string', 'size:2'],
            '*.phone' => ['nullable', 'string', 'max:30'],
            '*.email' => ['nullable', 'email', 'max:255'],
            '*.manager_name' => ['nullable', 'string', 'max:255'],
            '*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            '*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            '*.is_active' => ['nullable'],
            '*.is_primary' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
