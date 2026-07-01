<?php

declare(strict_types=1);

namespace App\Exports\Central;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlansImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'slug',
            'name',
            'description',
            'price',
            'currency',
            'interval',
            'is_active',
            'is_featured',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|float|bool|int>>
     */
    public function array(): array
    {
        return [
            [
                'growth',
                'Growth',
                'For teams scaling flash sales.',
                149.00,
                'USD',
                'monthly',
                true,
                false,
                4,
            ],
        ];
    }
}
