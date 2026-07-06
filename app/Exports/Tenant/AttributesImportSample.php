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
            [
                'Color',
                'color',
                'COLOR',
                'select',
                'swatch',
                true,
                true,
                true,
                1,
            ],
        ];
    }
}
