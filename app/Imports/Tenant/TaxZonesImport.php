<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\TaxZone;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TaxZonesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): TaxZone
    {
        return new TaxZone([
            'name' => (string) $row['name'],
            'country_code' => filled($row['country_code'] ?? null) ? (string) $row['country_code'] : null,
            'state' => $row['state'] ?? null,
            'city' => $row['city'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
            'postal_code_pattern' => $row['postal_code_pattern'] ?? null,
            'latitude' => filled($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
            'longitude' => filled($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
            'radius_km' => filled($row['radius_km'] ?? null) ? (float) $row['radius_km'] : null,
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
            '*.country_code' => ['nullable', 'string', 'size:2'],
            '*.state' => ['nullable', 'string', 'max:255'],
            '*.city' => ['nullable', 'string', 'max:255'],
            '*.postal_code' => ['nullable', 'string', 'max:20'],
            '*.postal_code_pattern' => ['nullable', 'string', 'max:50'],
            '*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            '*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            '*.radius_km' => ['nullable', 'numeric', 'min:0'],
            '*.is_default' => ['nullable'],
            '*.is_active' => ['nullable'],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
