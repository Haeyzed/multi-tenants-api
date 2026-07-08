<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxClassesImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'code',
            'description',
            'is_default',
            'is_active',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['Standard Rate', 'standard', 'Default taxable products and services.', true, true, 1],
            ['Reduced Rate', 'reduced', 'Essential goods with reduced tax rate.', false, true, 2],
            ['Zero Rate', 'zero', 'Tax-exempt goods with zero-rated reporting.', false, true, 3],
            ['Digital Goods', 'digital', 'Downloadable software, ebooks, and SaaS.', false, true, 4],
            ['Food & Groceries', 'food', 'Basic food items eligible for reduced tax.', false, true, 5],
            ['Medical Supplies', 'medical', 'Registered medical and health products.', false, true, 6],
            ['Books & Education', 'education', 'Educational materials and textbooks.', false, true, 7],
            ['Luxury Goods', 'luxury', 'High-value items with premium tax treatment.', false, true, 8],
            ['Export Sales', 'export', 'Cross-border export transactions.', false, true, 9],
            ['Non-Taxable', 'non-taxable', 'Items excluded from tax calculation.', false, true, 10],
            ['Children\'s Clothing', 'children-clothing', 'Apparel for children under reduced rate.', false, true, 11],
            ['Renewable Energy', 'renewable-energy', 'Solar panels and energy-efficient appliances.', false, true, 12],
            ['Hotel & Hospitality', 'hospitality', 'Accommodation and catering services.', false, true, 13],
            ['Financial Services', 'financial', 'Banking and insurance exempt services.', false, true, 14],
            ['Second-Hand Goods', 'second-hand', 'Used and refurbished product sales.', false, true, 15],
            ['Charity Donations', 'charity', 'Donation line items with special treatment.', false, true, 16],
            ['Shipping Only', 'shipping', 'Standalone shipping charges.', false, true, 17],
            ['Gift Cards', 'gift-cards', 'Stored-value gift card products.', false, true, 18],
            ['B2B Wholesale', 'b2b-wholesale', 'Business-to-business wholesale transactions.', false, true, 19],
            ['Interstate', 'interstate', 'Sales across state or regional boundaries.', false, true, 20],
        ];
    }
}
