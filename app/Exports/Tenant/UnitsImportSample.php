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
            ['Metric Ton', 't', 't', 'weight', 1000, false, 5],
            ['Meter', 'm', 'm', 'length', 1, true, 6],
            ['Centimeter', 'cm', 'cm', 'length', 0.01, false, 7],
            ['Millimeter', 'mm', 'mm', 'length', 0.001, false, 8],
            ['Inch', 'in', 'in', 'length', 0.0254, false, 9],
            ['Foot', 'ft', 'ft', 'length', 0.3048, false, 10],
            ['Liter', 'l', 'L', 'volume', 1, true, 11],
            ['Milliliter', 'ml', 'mL', 'volume', 0.001, false, 12],
            ['Gallon', 'gal', 'gal', 'volume', 3.78541, false, 13],
            ['Square Meter', 'sqm', 'm²', 'area', 1, true, 14],
            ['Square Foot', 'sqft', 'ft²', 'area', 0.092903, false, 15],
            ['Piece', 'pc', 'pc', 'count', 1, true, 16],
            ['Pair', 'pr', 'pr', 'count', 1, false, 17],
            ['Dozen', 'dz', 'dz', 'count', 12, false, 18],
            ['Box', 'box', 'box', 'count', 1, false, 19],
            ['Pack', 'pack', 'pack', 'count', 1, false, 20],
        ];
    }
}
