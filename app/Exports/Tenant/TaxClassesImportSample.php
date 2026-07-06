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
            ['Digital Goods', 'digital', 'Downloadable and SaaS products.', false, true, 4],
            ['Food & Groceries', 'food', 'Basic food items eligible for reduced tax.', false, true, 5],
            ['Medical Supplies', 'medical', 'Registered medical and health products.', false, true, 6],
            ['Books & Education', 'education', 'Educational materials and textbooks.', false, true, 7],
            ['Luxury Goods', 'luxury', 'High-value items with premium tax treatment.', false, true, 8],
            ['Export Sales', 'export', 'Cross-border export transactions.', false, true, 9],
            ['Non-Taxable', 'non-taxable', 'Items excluded from tax calculation.', false, true, 10],
        ];
    }
}
