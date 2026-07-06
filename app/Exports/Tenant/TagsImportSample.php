<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TagsImportSample implements FromArray, WithHeadings
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
            'icon',
            'is_visible',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['New Arrival', 'new-arrival', '#3B82F6', 'sparkles', true, 1],
            ['Best Seller', 'best-seller', '#F59E0B', 'star', true, 2],
            ['Limited Edition', 'limited-edition', '#EF4444', 'gem', true, 3],
            ['Eco Friendly', 'eco-friendly', '#22C55E', 'leaf', true, 4],
            ['Clearance', 'clearance', '#8B5CF6', 'tag', true, 5],
            ['Staff Pick', 'staff-pick', '#06B6D4', 'thumbs-up', true, 6],
            ['Trending', 'trending', '#EC4899', 'flame', true, 7],
            ['Gift Idea', 'gift-idea', '#14B8A6', 'gift', true, 8],
            ['Back In Stock', 'back-in-stock', '#10B981', 'package-check', true, 9],
            ['Online Exclusive', 'online-exclusive', '#6366F1', 'globe', true, 10],
        ];
    }
}
