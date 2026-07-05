<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\Warehouse;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WarehousesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Warehouse
    {
        $isPrimary = filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($isPrimary) {
            Warehouse::query()->where('is_primary', true)->update(['is_primary' => false]);
        }

        return new Warehouse([
            'name' => (string) $row['name'],
            'code' => (string) $row['code'],
            'description' => $row['description'] ?? null,
            'address_line_1' => $row['address_line_1'] ?? null,
            'address_line_2' => $row['address_line_2'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
            'country' => $row['country'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'manager_name' => $row['manager_name'] ?? null,
            'latitude' => filled($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
            'longitude' => filled($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_primary' => $isPrimary,
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
            '*.code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
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
