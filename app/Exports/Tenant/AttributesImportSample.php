<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttributesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'slug',
            'code',
            'type',
            'display_type',
            'is_filterable',
            'is_variant',
            'is_required',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['Color', 'color', 'COLOR', 'select', 'swatch', true, true, true, 1],
            ['Size', 'size', 'SIZE', 'select', 'dropdown', true, true, true, 2],
            ['Material', 'material', 'MATERIAL', 'select', 'dropdown', true, false, false, 3],
            ['Brand Line', 'brand-line', 'BRAND_LINE', 'text', 'text', false, false, false, 4],
            ['Warranty Period', 'warranty-period', 'WARRANTY', 'number', 'text', false, false, false, 5],
            ['Voltage', 'voltage', 'VOLTAGE', 'select', 'dropdown', true, false, false, 6],
            ['Capacity', 'capacity', 'CAPACITY', 'text', 'text', true, false, false, 7],
            ['Fit Type', 'fit-type', 'FIT', 'select', 'dropdown', true, true, false, 8],
            ['Flavor', 'flavor', 'FLAVOR', 'select', 'dropdown', true, true, false, 9],
            ['Pattern', 'pattern', 'PATTERN', 'select', 'swatch', true, false, false, 10],
        ];
    }
}
