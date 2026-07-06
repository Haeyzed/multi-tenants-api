<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsImportSample implements FromArray, WithHeadings
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
            'logo_media_id',
            'banner_media_id',
            'meta_title',
            'meta_description',
            'website_url',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            ['NorthPeak Outdoors', 'northpeak-outdoors', 'Premium hiking and camping equipment.', true, '', '', 'NorthPeak Outdoors', 'Explore rugged outdoor gear built for adventure.', 'https://northpeak-outdoors.com', 1],
            ['UrbanCraft Studio', 'urbancraft-studio', 'Modern furniture and home decor.', true, '', '', 'UrbanCraft Studio', 'Contemporary furniture for city living.', 'https://urbancraft.studio', 2],
            ['PureGlow Beauty', 'pureglow-beauty', 'Clean skincare and wellness products.', true, '', '', 'PureGlow Beauty', 'Natural beauty products for everyday routines.', 'https://pureglow.beauty', 3],
            ['SwiftTech Electronics', 'swifttech-electronics', 'Consumer electronics and smart devices.', true, '', '', 'SwiftTech Electronics', 'Reliable gadgets and accessories.', 'https://swifttech.io', 4],
            ['GreenHarvest Foods', 'greenharvest-foods', 'Organic pantry staples and snacks.', true, '', '', 'GreenHarvest Foods', 'Wholesome food products from trusted farms.', 'https://greenharvestfoods.com', 5],
            ['ActivePulse Sportswear', 'activepulse-sportswear', 'Performance apparel for athletes.', true, '', '', 'ActivePulse Sportswear', 'Train harder with breathable sportswear.', 'https://activepulse.com', 6],
            ['LittleNest Kids', 'littlenest-kids', 'Safe toys and nursery essentials.', true, '', '', 'LittleNest Kids', 'Quality products for babies and toddlers.', 'https://littlenestkids.com', 7],
            ['Artisan Brew Co.', 'artisan-brew-co', 'Specialty coffee and tea collections.', true, '', '', 'Artisan Brew Co.', 'Small-batch coffee roasted weekly.', 'https://artisanbrew.co', 8],
            ['EcoPack Solutions', 'ecopack-solutions', 'Sustainable packaging materials.', true, '', '', 'EcoPack Solutions', 'Eco-friendly packaging for modern brands.', 'https://ecopack.solutions', 9],
            ['Heritage Leather Works', 'heritage-leather-works', 'Handcrafted leather bags and belts.', true, '', '', 'Heritage Leather Works', 'Timeless leather goods made to last.', 'https://heritageleather.works', 10],
        ];
    }
}
