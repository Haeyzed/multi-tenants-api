<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnitsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'code',
            'symbol',
            'type',
            'conversion_factor',
            'is_base',
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
                'Kilogram',
                'kg',
                'kg',
                'weight',
                1,
                true,
                1,
            ],
            [
                'Gram',
                'g',
                'g',
                'weight',
                0.001,
                false,
                2,
            ],
        ];
    }
}
