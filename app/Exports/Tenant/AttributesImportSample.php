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
            ['Storage Capacity', 'storage-capacity', 'STORAGE', 'select', 'dropdown', true, true, true, 3],
            ['RAM', 'ram', 'RAM', 'select', 'dropdown', true, true, false, 4],
            ['Screen Size', 'screen-size', 'SCREEN', 'select', 'dropdown', true, false, false, 5],
            ['Material', 'material', 'MATERIAL', 'select', 'dropdown', true, false, false, 6],
            ['Connectivity', 'connectivity', 'CONNECT', 'select', 'dropdown', true, false, false, 7],
            ['Battery Life', 'battery-life', 'BATTERY', 'text', 'text', false, false, false, 8],
            ['Warranty', 'warranty', 'WARRANTY', 'select', 'dropdown', false, false, false, 9],
            ['Weight', 'weight', 'WEIGHT', 'number', 'text', true, false, false, 10],
            ['Fit Type', 'fit-type', 'FIT', 'select', 'dropdown', true, true, false, 11],
            ['Flavor', 'flavor', 'FLAVOR', 'select', 'dropdown', true, true, false, 12],
            ['Voltage', 'voltage', 'VOLTAGE', 'select', 'dropdown', true, false, false, 13],
            ['Capacity', 'capacity', 'CAPACITY', 'text', 'text', true, false, false, 14],
            ['Pattern', 'pattern', 'PATTERN', 'select', 'swatch', true, false, false, 15],
            ['Operating System', 'operating-system', 'OS', 'select', 'dropdown', true, false, false, 16],
            ['Resolution', 'resolution', 'RESOLUTION', 'select', 'dropdown', true, false, false, 17],
            ['Refresh Rate', 'refresh-rate', 'REFRESH', 'select', 'dropdown', true, false, false, 18],
            ['Water Resistance', 'water-resistance', 'WATER_RES', 'select', 'dropdown', true, false, false, 19],
            ['Country of Origin', 'country-of-origin', 'ORIGIN', 'text', 'text', false, false, false, 20],
        ];
    }
}
