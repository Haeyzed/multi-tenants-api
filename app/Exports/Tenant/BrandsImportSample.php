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
            ['Apple', 'apple', 'Consumer electronics, software, and services including iPhone, Mac, and iPad.', true, '', '', 'Apple', 'Think different. Shop iPhone, Mac, iPad, and accessories.', 'https://www.apple.com', 1],
            ['Samsung', 'samsung', 'Global leader in smartphones, TVs, appliances, and memory technology.', true, '', '', 'Samsung', 'Innovation for everyday life across mobile and home electronics.', 'https://www.samsung.com', 2],
            ['Nike', 'nike', 'Athletic footwear, apparel, and equipment for sport and lifestyle.', true, '', '', 'Nike', 'Just Do It. Performance gear for athletes worldwide.', 'https://www.nike.com', 3],
            ['Adidas', 'adidas', 'Sports shoes, clothing, and accessories for training and streetwear.', true, '', '', 'Adidas', 'Impossible is nothing. Iconic sportswear and sneakers.', 'https://www.adidas.com', 4],
            ['Sony', 'sony', 'Electronics, gaming, audio, imaging, and entertainment products.', true, '', '', 'Sony', 'Premium TVs, cameras, headphones, and PlayStation.', 'https://www.sony.com', 5],
            ['LG', 'lg', 'Home appliances, OLED TVs, monitors, and mobile devices.', true, '', '', 'LG', 'Life\'s Good with smart home and display technology.', 'https://www.lg.com', 6],
            ['Dell', 'dell', 'Laptops, desktops, servers, and enterprise IT solutions.', true, '', '', 'Dell', 'Business and consumer PCs built for productivity.', 'https://www.dell.com', 7],
            ['HP', 'hp', 'Printers, laptops, desktops, and workplace technology.', true, '', '', 'HP', 'Computing and printing solutions for home and office.', 'https://www.hp.com', 8],
            ['Canon', 'canon', 'Digital cameras, lenses, printers, and imaging equipment.', true, '', '', 'Canon', 'Professional and consumer photography gear.', 'https://www.canon.com', 9],
            ['Dyson', 'dyson', 'Vacuum cleaners, air purifiers, hair care, and home technology.', true, '', '', 'Dyson', 'Engineered home appliances with advanced airflow design.', 'https://www.dyson.com', 10],
            ['Philips', 'philips', 'Personal health, lighting, and consumer appliances.', true, '', '', 'Philips', 'Health tech, grooming, and smart lighting products.', 'https://www.philips.com', 11],
            ['Bose', 'bose', 'Premium headphones, speakers, and audio systems.', true, '', '', 'Bose', 'Immersive sound for music, travel, and home theater.', 'https://www.bose.com', 12],
            ['Levi\'s', 'levis', 'Denim jeans, jackets, and casual apparel since 1853.', true, '', '', 'Levi\'s', 'Original denim and timeless American style.', 'https://www.levi.com', 13],
            ['IKEA', 'ikea', 'Affordable furniture, storage, and home accessories.', true, '', '', 'IKEA', 'Well-designed furniture for every room in your home.', 'https://www.ikea.com', 14],
            ['KitchenAid', 'kitchenaid', 'Stand mixers, blenders, and premium kitchen appliances.', true, '', '', 'KitchenAid', 'Professional-grade tools for home chefs.', 'https://www.kitchenaid.com', 15],
            ['Microsoft', 'microsoft', 'Surface devices, software, gaming, and cloud services.', true, '', '', 'Microsoft', 'Surface PCs, Xbox, and productivity software.', 'https://www.microsoft.com', 16],
            ['Google', 'google', 'Pixel phones, Nest smart home, and consumer hardware.', true, '', '', 'Google', 'Pixel smartphones and connected home devices.', 'https://store.google.com', 17],
            ['Black+Decker', 'black-decker', 'Power tools, home improvement, and small appliances.', true, '', '', 'Black+Decker', 'DIY tools and kitchen appliances for everyday use.', 'https://www.blackanddecker.com', 18],
            ['Patagonia', 'patagonia', 'Outdoor clothing and gear built for environmental responsibility.', true, '', '', 'Patagonia', 'Sustainable jackets, fleeces, and adventure apparel.', 'https://www.patagonia.com', 19],
            ['Uniqlo', 'uniqlo', 'Japanese casual wear with LifeWear essentials for all seasons.', true, '', '', 'Uniqlo', 'Quality basics, heattech, and minimalist everyday fashion.', 'https://www.uniqlo.com', 20],
        ];
    }
}
