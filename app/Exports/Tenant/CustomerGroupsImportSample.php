<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerGroupsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'description',
            'discount_percent',
            'is_active',
        ];
    }

    /**
     * @return list<list<string|bool|int|float>>
     */
    public function array(): array
    {
        return [
            ['Retail', 'Standard walk-in and online shoppers.', 0, true],
            ['VIP', 'High-value repeat customers with loyalty perks.', 10, true],
            ['Wholesale', 'Bulk buyers with negotiated pricing.', 15, true],
            ['Staff', 'Employees eligible for internal discounts.', 20, true],
            ['Students', 'Verified students with education discount.', 8, true],
            ['Corporate', 'Business accounts with invoicing terms.', 12, true],
            ['Affiliates', 'Partners promoting the store.', 5, true],
            ['Members', 'Paid membership program subscribers.', 7, true],
            ['First-Time Buyers', 'Welcome discount for new customers.', 5, true],
            ['Inactive Win-Back', 'Re-engagement offers for dormant accounts.', 10, true],
            ['Seniors', 'Customers aged 65+ with senior discount.', 8, true],
            ['Military & Veterans', 'Verified service members and veterans.', 12, true],
            ['Healthcare Workers', 'Nurses, doctors, and hospital staff.', 10, true],
            ['Teachers', 'Educators with verified school email.', 8, true],
            ['Non-Profit', 'Registered charities and NGOs.', 15, true],
            ['Resellers', 'Authorized resellers with tiered pricing.', 18, true],
            ['Early Access', 'Pre-launch and beta product access group.', 5, true],
            ['Birthday Club', 'Birthday month special offers.', 10, true],
            ['Local Pickup', 'Customers who collect orders in-store.', 3, true],
            ['International', 'Cross-border shoppers with export pricing.', 5, true],
        ];
    }
}
