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
            ['Free Shipping', 'free-shipping', '#0EA5E9', 'truck', true, 11],
            ['Apple Compatible', 'apple-compatible', '#64748B', 'smartphone', true, 12],
            ['Wireless', 'wireless', '#A855F7', 'wifi', true, 13],
            ['Waterproof', 'waterproof', '#0284C7', 'droplets', true, 14],
            ['Organic', 'organic', '#84CC16', 'sprout', true, 15],
            ['Made in USA', 'made-in-usa', '#DC2626', 'flag', true, 16],
            ['Refurbished', 'refurbished', '#78716C', 'refresh-cw', true, 17],
            ['Bundle Deal', 'bundle-deal', '#D97706', 'layers', true, 18],
            ['Holiday Sale', 'holiday-sale', '#BE123C', 'party-popper', true, 19],
            ['Members Only', 'members-only', '#4F46E5', 'lock', true, 20],
        ];
    }
}
