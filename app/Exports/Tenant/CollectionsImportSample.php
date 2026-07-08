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
            ['Apple Essentials', 'apple-essentials', 'iPhone, Mac, iPad, and Apple accessories in one collection.', true, true, 'manual', 1],
            ['Samsung Galaxy Lineup', 'samsung-galaxy-lineup', 'Latest Galaxy phones, tablets, and wearables.', true, true, 'manual', 2],
            ['Running Shoes', 'running-shoes', 'Nike, Adidas, and performance trainers for road and trail.', true, true, 'manual', 3],
            ['Home Office Setup', 'home-office-setup', 'Laptops, monitors, printers, and desk accessories.', true, false, 'manual', 4],
            ['Kitchen Must-Haves', 'kitchen-must-haves', 'Mixers, cookware, and small appliances for everyday cooking.', true, false, 'manual', 5],
            ['Premium Audio', 'premium-audio', 'Headphones and speakers from Sony, Bose, and Apple.', true, true, 'manual', 6],
            ['Photography Pro', 'photography-pro', 'Cameras, lenses, and gear for enthusiasts and creators.', true, false, 'manual', 7],
            ['Smart Home', 'smart-home', 'Nest, smart displays, and connected home devices.', true, false, 'manual', 8],
            ['Back to School', 'back-to-school', 'Laptops, backpacks, and supplies for students.', true, true, 'manual', 9],
            ['Holiday Gift Guide', 'holiday-gift-guide', 'Curated gifts across electronics, fashion, and home.', true, true, 'manual', 10],
            ['Under $100', 'under-100', 'Affordable picks across all categories.', true, false, 'manual', 11],
            ['New This Week', 'new-this-week', 'Fresh arrivals added in the last seven days.', true, false, 'manual', 12],
            ['Fitness & Wellness', 'fitness-wellness', 'Wearables, sportswear, and recovery gear.', true, false, 'manual', 13],
            ['Living Room Refresh', 'living-room-refresh', 'TVs, soundbars, sofas, and decor.', true, false, 'manual', 14],
            ['Work From Anywhere', 'work-from-anywhere', 'Portable laptops, tablets, and noise-canceling headphones.', true, false, 'manual', 15],
            ['Outdoor Adventure', 'outdoor-adventure', 'Patagonia apparel and rugged gear for travel.', true, false, 'manual', 16],
            ['Beauty Favorites', 'beauty-favorites', 'Top-rated grooming and personal care products.', true, false, 'manual', 17],
            ['Gaming & Entertainment', 'gaming-entertainment', 'Consoles, headsets, and 4K TVs.', true, false, 'manual', 18],
            ['Sustainable Living', 'sustainable-living', 'Eco-conscious brands and energy-efficient appliances.', true, false, 'manual', 19],
            ['Flash Deals', 'flash-deals', 'Limited-time discounts updated weekly.', true, true, 'manual', 20],
        ];
    }
}
