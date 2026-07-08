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
            ['Free Shipping', 'free-shipping', '#FFFFFF', '#0EA5E9', 'truck', true, 11],
            ['Preorder', 'preorder', '#FFFFFF', '#7C3AED', 'clock', true, 12],
            ['Refurbished', 'refurbished', '#FFFFFF', '#78716C', 'refresh-cw', true, 13],
            ['Bundle', 'bundle', '#FFFFFF', '#D97706', 'layers', true, 14],
            ['Top Rated', 'top-rated', '#FFFFFF', '#EAB308', 'award', true, 15],
            ['Staff Pick', 'staff-pick', '#FFFFFF', '#0891B2', 'thumbs-up', true, 16],
            ['Clearance', 'clearance', '#FFFFFF', '#BE123C', 'tag', true, 17],
            ['Members Only', 'members-only', '#FFFFFF', '#4F46E5', 'lock', true, 18],
            ['Low Stock', 'low-stock', '#FFFFFF', '#F97316', 'alert-triangle', true, 19],
            ['New Season', 'new-season', '#FFFFFF', '#059669', 'sun', true, 20],
        ];
    }
}
