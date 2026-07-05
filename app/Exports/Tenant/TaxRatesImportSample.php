<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxRatesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'tax_class_id',
            'tax_zone_id',
            'rate',
            'priority',
            'is_compound',
            'applies_to_shipping',
            'effective_from',
            'effective_to',
            'is_active',
        ];
    }

    /**
     * @return list<list<string|bool|int|float|null>>
     */
    public function array(): array
    {
        return [
            [
                'Standard VAT',
                1,
                1,
                20.0,
                1,
                false,
                false,
                null,
                null,
                true,
            ],
        ];
    }
}
