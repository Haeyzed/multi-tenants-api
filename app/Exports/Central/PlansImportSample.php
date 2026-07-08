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
            ['starter', 'Starter', 'For new stores getting online quickly with core catalog features.', 29.00, 'USD', 'monthly', true, false, 1],
            ['growth', 'Growth', 'For growing teams scaling campaigns, exports, and integrations.', 149.00, 'USD', 'monthly', true, true, 2],
            ['business', 'Business', 'Advanced tools for multi-location retailers and B2B sales.', 299.00, 'USD', 'monthly', true, false, 3],
            ['enterprise', 'Enterprise', 'Custom limits, SSO, dedicated support, and SLA.', 799.00, 'USD', 'monthly', true, false, 4],
            ['starter-yearly', 'Starter Yearly', 'Starter plan billed annually with 2 months free.', 290.00, 'USD', 'yearly', true, false, 5],
            ['growth-yearly', 'Growth Yearly', 'Growth plan billed annually with 2 months free.', 1490.00, 'USD', 'yearly', true, true, 6],
            ['business-yearly', 'Business Yearly', 'Business plan billed annually with 2 months free.', 2990.00, 'USD', 'yearly', true, false, 7],
            ['trial', 'Trial', '14-day evaluation plan for new tenants.', 0.00, 'USD', 'monthly', true, false, 8],
            ['agency', 'Agency', 'Manage multiple client stores from one central account.', 499.00, 'USD', 'monthly', true, false, 9],
            ['marketplace', 'Marketplace', 'Multi-vendor marketplace with vendor dashboards.', 999.00, 'USD', 'monthly', true, false, 10],
            ['starter-ngn', 'Starter NGN', 'Starter plan priced in Nigerian Naira.', 45000.00, 'NGN', 'monthly', true, false, 11],
            ['growth-ngn', 'Growth NGN', 'Growth plan priced in Nigerian Naira.', 225000.00, 'NGN', 'monthly', true, false, 12],
            ['business-ngn', 'Business NGN', 'Business plan priced in Nigerian Naira.', 450000.00, 'NGN', 'monthly', true, false, 13],
            ['starter-gbp', 'Starter GBP', 'Starter plan priced in British Pounds.', 25.00, 'GBP', 'monthly', true, false, 14],
            ['growth-gbp', 'Growth GBP', 'Growth plan priced in British Pounds.', 119.00, 'GBP', 'monthly', true, false, 15],
            ['business-gbp', 'Business GBP', 'Business plan priced in British Pounds.', 239.00, 'GBP', 'monthly', true, false, 16],
            ['starter-eur', 'Starter EUR', 'Starter plan priced in Euros.', 27.00, 'EUR', 'monthly', true, false, 17],
            ['growth-eur', 'Growth EUR', 'Growth plan priced in Euros.', 139.00, 'EUR', 'monthly', true, false, 18],
            ['business-eur', 'Business EUR', 'Business plan priced in Euros.', 279.00, 'EUR', 'monthly', true, false, 19],
            ['enterprise-yearly', 'Enterprise Yearly', 'Enterprise plan billed annually.', 7990.00, 'USD', 'yearly', true, false, 20],
        ];
    }
}
