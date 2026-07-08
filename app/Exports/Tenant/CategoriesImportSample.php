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
            ['Smartphones', 'smartphones', 'iPhone, Galaxy, Pixel, and Android phones.', 'Shop Smartphones', 'Compare the latest smartphones from top brands.', '', true, 1, '', '', '#2563eb', 'smartphone'],
            ['Laptops & Computers', 'laptops-computers', 'Notebooks, ultrabooks, desktops, and workstations.', 'Laptops & Computers', 'Powerful laptops and PCs for work and gaming.', '', true, 2, '', '', '#1d4ed8', 'laptop'],
            ['Tablets', 'tablets', 'iPad, Galaxy Tab, and Android tablets.', 'Shop Tablets', 'Portable tablets for reading, work, and entertainment.', '', true, 3, '', '', '#3b82f6', 'tablet'],
            ['TVs & Home Theater', 'tvs-home-theater', 'OLED, QLED, soundbars, and streaming devices.', 'TVs & Home Theater', 'Big-screen TVs and home cinema accessories.', '', true, 4, '', '', '#7c3aed', 'tv'],
            ['Audio & Headphones', 'audio-headphones', 'Wireless earbuds, over-ear headphones, and speakers.', 'Audio & Headphones', 'Premium sound for music, calls, and travel.', '', true, 5, '', '', '#8b5cf6', 'headphones'],
            ['Cameras & Photography', 'cameras-photography', 'Mirrorless cameras, lenses, drones, and accessories.', 'Cameras & Photography', 'Capture moments with pro and enthusiast gear.', '', true, 6, '', '', '#6366f1', 'camera'],
            ['Men\'s Clothing', 'mens-clothing', 'Shirts, jeans, jackets, and everyday menswear.', 'Men\'s Clothing', 'Style essentials and seasonal fashion for men.', '', true, 7, '', '', '#0f766e', 'shirt'],
            ['Women\'s Clothing', 'womens-clothing', 'Dresses, tops, pants, and activewear for women.', 'Women\'s Clothing', 'Trend-led apparel and wardrobe staples.', '', true, 8, '', '', '#db2777', 'sparkles'],
            ['Footwear', 'footwear', 'Running shoes, sneakers, boots, and sandals.', 'Shop Footwear', 'Comfort and performance shoes for every occasion.', '', true, 9, '', '', '#ea580c', 'footprints'],
            ['Home & Kitchen', 'home-kitchen', 'Cookware, utensils, storage, and small appliances.', 'Home & Kitchen', 'Everything you need to cook and organize your kitchen.', '', true, 10, '', '', '#f59e0b', 'utensils'],
            ['Furniture', 'furniture', 'Sofas, beds, desks, shelving, and dining sets.', 'Shop Furniture', 'Furnish every room with quality pieces.', '', true, 11, '', '', '#b45309', 'sofa'],
            ['Beauty & Personal Care', 'beauty-personal-care', 'Skincare, grooming, hair care, and oral care.', 'Beauty & Personal Care', 'Daily self-care from trusted brands.', '', true, 12, '', '', '#ec4899', 'heart-pulse'],
            ['Sports & Fitness', 'sports-fitness', 'Gym equipment, yoga mats, sportswear, and accessories.', 'Sports & Fitness', 'Gear up for training, running, and outdoor sports.', '', true, 13, '', '', '#16a34a', 'dumbbell'],
            ['Toys & Games', 'toys-games', 'Action figures, board games, LEGO, and educational toys.', 'Toys & Games', 'Fun gifts and learning toys for kids of all ages.', '', true, 14, '', '', '#f97316', 'gamepad-2'],
            ['Books', 'books', 'Fiction, non-fiction, textbooks, and audiobooks.', 'Shop Books', 'Read, learn, and discover new stories.', '', true, 15, '', '', '#0d9488', 'book-open'],
            ['Office Supplies', 'office-supplies', 'Printers, paper, pens, desks, and organizers.', 'Office Supplies', 'Stock your workspace for productivity.', '', true, 16, '', '', '#64748b', 'briefcase'],
            ['Appliances', 'appliances', 'Vacuums, air purifiers, washers, and large home appliances.', 'Home Appliances', 'Reliable appliances for a cleaner, smarter home.', '', true, 17, '', '', '#475569', 'wind'],
            ['Watches & Jewelry', 'watches-jewelry', 'Smartwatches, analog watches, rings, and necklaces.', 'Watches & Jewelry', 'Timepieces and fine accessories.', '', true, 18, '', '', '#a855f7', 'watch'],
            ['Baby & Kids', 'baby-kids', 'Strollers, nursery gear, kids clothing, and feeding essentials.', 'Baby & Kids', 'Safe, practical products for growing families.', '', true, 19, '', '', '#14b8a6', 'baby'],
            ['Automotive', 'automotive', 'Car accessories, tools, cleaning kits, and maintenance products.', 'Automotive', 'Maintain and upgrade your vehicle.', '', true, 20, '', '', '#334155', 'car'],
        ];
    }
}
