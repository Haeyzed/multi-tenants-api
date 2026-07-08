<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxZonesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'country_code',
            'state',
            'city',
            'postal_code',
            'postal_code_pattern',
            'latitude',
            'longitude',
            'radius_km',
            'is_default',
            'is_active',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Nigeria Nationwide', 'NG', null, null, null, null, null, null, null, true, true, 1],
            ['Lagos State', 'NG', 'Lagos', null, null, null, 6.5244, 3.3792, null, false, true, 2],
            ['Abuja FCT', 'NG', 'FCT', 'Abuja', null, null, 9.0765, 7.3986, null, false, true, 3],
            ['United Kingdom', 'GB', null, null, null, null, null, null, null, false, true, 4],
            ['United States - California', 'US', 'CA', null, null, null, 36.7783, -119.4179, null, false, true, 5],
            ['United States - New York', 'US', 'NY', null, null, null, 40.7128, -74.0060, null, false, true, 6],
            ['United States - Texas', 'US', 'TX', null, null, null, 31.9686, -99.9018, null, false, true, 7],
            ['Ghana Nationwide', 'GH', null, null, null, null, null, null, null, false, true, 8],
            ['Kenya Nationwide', 'KE', null, null, null, null, null, null, null, false, true, 9],
            ['South Africa - Gauteng', 'ZA', 'GP', 'Johannesburg', null, null, -26.2041, 28.0473, null, false, true, 10],
            ['Germany', 'DE', null, null, null, null, null, null, null, false, true, 11],
            ['France', 'FR', null, null, null, null, null, null, null, false, true, 12],
            ['Canada - Ontario', 'CA', 'ON', null, null, null, 43.6532, -79.3832, null, false, true, 13],
            ['Australia - New South Wales', 'AU', 'NSW', 'Sydney', null, null, -33.8688, 151.2093, null, false, true, 14],
            ['United Arab Emirates', 'AE', null, null, null, null, null, null, null, false, true, 15],
            ['India', 'IN', null, null, null, null, null, null, null, false, true, 16],
            ['Brazil', 'BR', null, null, null, null, null, null, null, false, true, 17],
            ['Japan', 'JP', null, null, null, null, null, null, null, false, true, 18],
            ['Singapore', 'SG', null, null, null, null, null, null, null, false, true, 19],
            ['European Union VAT', 'EU', null, null, null, null, null, null, null, false, true, 20],
        ];
    }
}
