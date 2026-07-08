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
            ['NG Standard VAT', 1, 1, 7.5, 1, false, false, '2024-01-01', null, true],
            ['Lagos Consumption Tax', 1, 2, 1.0, 2, false, false, '2024-01-01', null, true],
            ['NG Reduced Food Rate', 2, 1, 5.0, 1, false, false, '2024-01-01', null, true],
            ['NG Digital Services VAT', 4, 1, 7.5, 1, false, false, '2024-01-01', null, true],
            ['UK Standard VAT', 1, 4, 20.0, 1, false, true, '2024-01-01', null, true],
            ['US CA Sales Tax', 1, 5, 7.25, 1, false, true, '2024-01-01', null, true],
            ['US NY Sales Tax', 1, 6, 8.0, 1, false, true, '2024-01-01', null, true],
            ['US TX Sales Tax', 1, 7, 6.25, 1, false, true, '2024-01-01', null, true],
            ['Ghana VAT', 1, 8, 15.0, 1, false, false, '2024-01-01', null, true],
            ['Kenya VAT', 1, 9, 16.0, 1, false, false, '2024-01-01', null, true],
            ['South Africa VAT', 1, 10, 15.0, 1, false, false, '2024-01-01', null, true],
            ['Germany VAT', 1, 11, 19.0, 1, false, true, '2024-01-01', null, true],
            ['France VAT', 1, 12, 20.0, 1, false, true, '2024-01-01', null, true],
            ['Canada Ontario HST', 1, 13, 13.0, 1, false, true, '2024-01-01', null, true],
            ['Australia GST', 1, 14, 10.0, 1, false, true, '2024-01-01', null, true],
            ['UAE VAT', 1, 15, 5.0, 1, false, false, '2024-01-01', null, true],
            ['India GST', 1, 16, 18.0, 1, false, false, '2024-01-01', null, true],
            ['Brazil ICMS', 1, 17, 17.0, 1, false, false, '2024-01-01', null, true],
            ['Japan Consumption Tax', 1, 18, 10.0, 1, false, false, '2024-01-01', null, true],
            ['Export Zero Rate', 9, 1, 0.0, 1, false, false, '2024-01-01', null, true],
        ];
    }
}
