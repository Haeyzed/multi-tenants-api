<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxZonesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'country_code',
            'state',
            'city',
            'postal_code',
            'postal_code_pattern',
            'latitude',
            'longitude',
            'radius_km',
            'is_default',
            'is_active',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int|float|null>>
     */
    public function array(): array
    {
        return [
            [
                'United Kingdom',
                'GB',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                true,
                true,
                1,
            ],
        ];
    }
}
