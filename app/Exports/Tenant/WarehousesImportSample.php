<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehousesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'code',
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
            'is_active',
            'is_primary',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int|float>>
     */
    public function array(): array
    {
        return [
            [
                'Main Warehouse',
                'WH-MAIN',
                'Primary storage facility.',
                '123 Industrial Ave',
                '',
                'Lagos',
                'Lagos',
                '100001',
                'NG',
                '+2348000000000',
                'warehouse@example.test',
                'John Manager',
                6.5244,
                3.3792,
                true,
                true,
                1,
            ],
        ];
    }
}
