<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductLabelsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'slug',
            'color',
            'background_color',
            'icon',
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
            ['New', 'new', '#FFFFFF', '#3B82F6', 'sparkles', true, 1],
            ['Sale', 'sale', '#FFFFFF', '#EF4444', 'percent', true, 2],
            ['Hot', 'hot', '#FFFFFF', '#F59E0B', 'flame', true, 3],
            ['Limited', 'limited', '#FFFFFF', '#8B5CF6', 'gem', true, 4],
            ['Eco', 'eco', '#FFFFFF', '#22C55E', 'leaf', true, 5],
            ['Best Seller', 'best-seller', '#FFFFFF', '#06B6D4', 'star', true, 6],
            ['Trending', 'trending', '#FFFFFF', '#EC4899', 'trending-up', true, 7],
            ['Gift', 'gift', '#FFFFFF', '#14B8A6', 'gift', true, 8],
            ['Back In Stock', 'back-in-stock', '#FFFFFF', '#10B981', 'package-check', true, 9],
            ['Exclusive', 'exclusive', '#FFFFFF', '#6366F1', 'globe', true, 10],
        ];
    }
}
