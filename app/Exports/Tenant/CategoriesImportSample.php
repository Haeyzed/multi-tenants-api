<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoriesImportSample implements FromArray, WithHeadings
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
            'meta_title',
            'meta_description',
            'parent_id',
            'is_visible',
            'sort_order',
            'image_media_id',
            'banner_media_id',
            'color',
            'icon',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['Electronics', 'electronics', 'Phones, laptops, audio, and accessories.', 'Shop Electronics', 'Browse the latest electronics and gadgets.', '', true, 1, '', '', '#2563eb', 'laptop'],
            ['Clothing', 'clothing', 'Apparel for men, women, and children.', 'Shop Clothing', 'Discover fashion for every season.', '', true, 2, '', '', '#db2777', 'shirt'],
            ['Home & Kitchen', 'home-kitchen', 'Cookware, appliances, and home essentials.', 'Home & Kitchen', 'Upgrade your home with quality products.', '', true, 3, '', '', '#ea580c', 'home'],
            ['Sports & Outdoors', 'sports', 'Fitness gear, sportswear, and outdoor equipment.', 'Sports & Outdoors', 'Gear up for your active lifestyle.', '', true, 4, '', '', '#16a34a', 'dumbbell'],
            ['Beauty & Personal Care', 'beauty', 'Skincare, haircare, and grooming products.', 'Beauty & Personal Care', 'Self-care products from trusted brands.', '', true, 5, '', '', '#a855f7', 'sparkles'],
            ['Office Supplies', 'office', 'Stationery, printers, and workspace accessories.', 'Office Supplies', 'Everything you need for a productive office.', '', true, 6, '', '', '#64748b', 'briefcase'],
            ['Books & Media', 'books-media', 'Books, magazines, and digital media.', 'Books & Media', 'Read, learn, and stay inspired.', '', true, 7, '', '', '#0d9488', 'book-open'],
            ['Toys & Games', 'toys-games', 'Toys, board games, and educational kits.', 'Toys & Games', 'Fun products for kids and families.', '', true, 8, '', '', '#f59e0b', 'gamepad-2'],
            ['Automotive', 'automotive', 'Car accessories, tools, and maintenance products.', 'Automotive', 'Maintain and customize your vehicle.', '', true, 9, '', '', '#475569', 'car'],
            ['Health & Wellness', 'health-wellness', 'Vitamins, supplements, and wellness devices.', 'Health & Wellness', 'Support your health goals every day.', '', true, 10, '', '', '#22c55e', 'heart-pulse'],
        ];
    }
}
