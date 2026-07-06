<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CollectionsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'slug',
            'description',
            'is_visible',
            'is_featured',
            'type',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['Summer Sale', 'summer-sale', 'Seasonal discounts on warm-weather essentials.', true, true, 'manual', 1],
            ['Back to School', 'back-to-school', 'Notebooks, backpacks, and classroom supplies.', true, true, 'manual', 2],
            ['Holiday Gift Guide', 'holiday-gift-guide', 'Curated gifts for friends and family.', true, true, 'manual', 3],
            ['Work From Home', 'work-from-home', 'Desk accessories and home office upgrades.', true, false, 'manual', 4],
            ['Fitness Essentials', 'fitness-essentials', 'Top picks for home and gym workouts.', true, false, 'manual', 5],
            ['Kitchen Favorites', 'kitchen-favorites', 'Best-selling cookware and appliances.', true, false, 'manual', 6],
            ['New Customer Picks', 'new-customer-picks', 'Starter products for first-time shoppers.', true, false, 'manual', 7],
            ['Premium Selection', 'premium-selection', 'High-end products with premium quality.', true, true, 'manual', 8],
            ['Under $25', 'under-25', 'Affordable products under twenty-five dollars.', true, false, 'manual', 9],
            ['Flash Deals', 'flash-deals', 'Short-lived promotions updated weekly.', true, true, 'manual', 10],
        ];
    }
}
