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
            ['Kilogram', 'kg', 'kg', 'weight', 1, true, 1],
            ['Gram', 'g', 'g', 'weight', 0.001, false, 2],
            ['Pound', 'lb', 'lb', 'weight', 0.453592, false, 3],
            ['Ounce', 'oz', 'oz', 'weight', 0.0283495, false, 4],
            ['Meter', 'm', 'm', 'dimension', 1, true, 5],
            ['Centimeter', 'cm', 'cm', 'dimension', 0.01, false, 6],
            ['Inch', 'in', 'in', 'dimension', 0.0254, false, 7],
            ['Liter', 'l', 'L', 'volume', 1, true, 8],
            ['Milliliter', 'ml', 'mL', 'volume', 0.001, false, 9],
            ['Piece', 'pc', 'pc', 'quantity', 1, true, 10],
        ];
    }
}
