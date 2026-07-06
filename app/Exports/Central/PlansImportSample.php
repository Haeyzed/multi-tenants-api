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
            ['starter', 'Starter', 'For new stores getting online quickly.', 29.00, 'USD', 'monthly', true, false, 1],
            ['growth', 'Growth', 'For teams scaling flash sales and campaigns.', 149.00, 'USD', 'monthly', true, true, 2],
            ['business', 'Business', 'Advanced tools for multi-location retailers.', 299.00, 'USD', 'monthly', true, false, 3],
            ['enterprise', 'Enterprise', 'Custom limits, SSO, and dedicated support.', 799.00, 'USD', 'monthly', true, false, 4],
            ['starter-yearly', 'Starter Yearly', 'Starter plan billed annually.', 290.00, 'USD', 'yearly', true, false, 5],
            ['growth-yearly', 'Growth Yearly', 'Growth plan billed annually.', 1490.00, 'USD', 'yearly', true, true, 6],
            ['business-yearly', 'Business Yearly', 'Business plan billed annually.', 2990.00, 'USD', 'yearly', true, false, 7],
            ['trial', 'Trial', '14-day evaluation plan for new tenants.', 0.00, 'USD', 'monthly', true, false, 8],
            ['agency', 'Agency', 'Manage multiple client stores from one account.', 499.00, 'USD', 'monthly', true, false, 9],
            ['marketplace', 'Marketplace', 'Multi-vendor marketplace capabilities.', 999.00, 'USD', 'monthly', true, false, 10],
        ];
    }
}
