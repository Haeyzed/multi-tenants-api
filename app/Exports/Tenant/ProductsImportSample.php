<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'slug',
            'sku',
            'price',
            'compare_at_price',
            'type',
            'status',
            'visibility',
            'summary',
            'description',
            'brand_slug',
            'category_slug',
            'track_inventory',
            'is_featured',
        ];
    }

    /**
     * @return list<list<string|bool|float>>
     */
    public function array(): array
    {
        return [
            ['iPhone 15 Pro', 'iphone-15-pro', 'APL-IP15P-128', 999.00, 1099.00, 'simple', 'draft', 'visible', 'Titanium design with A17 Pro chip and 48MP camera system.', 'Apple iPhone 15 Pro with USB-C, Action button, and all-day battery life. Import brands and categories first, then set status to active when ready to publish.', 'apple', 'smartphones', true, true],
            ['MacBook Air 13" M3', 'macbook-air-13-m3', 'APL-MBA13-M3', 1099.00, 1199.00, 'simple', 'draft', 'visible', 'Ultra-thin laptop with Apple M3 chip and 18-hour battery.', '13.6-inch Liquid Retina display, 8GB RAM, 256GB SSD. Ideal for students and professionals.', 'apple', 'laptops-computers', true, true],
            ['iPad Air (M2)', 'ipad-air-m2', 'APL-IPAD-AIR-M2', 599.00, 649.00, 'simple', 'draft', 'visible', 'Versatile tablet with M2 performance and Apple Pencil support.', '10.9-inch display, Touch ID, and all-day battery for work and creativity.', 'apple', 'tablets', true, false],
            ['Galaxy S24 Ultra', 'galaxy-s24-ultra', 'SAM-GS24U-256', 1299.99, 1399.99, 'simple', 'draft', 'visible', 'Flagship Android phone with S Pen and 200MP camera.', 'Samsung Galaxy S24 Ultra with titanium frame, AI features, and 5000mAh battery.', 'samsung', 'smartphones', true, true],
            ['Galaxy Tab S9', 'galaxy-tab-s9', 'SAM-TABS9-128', 799.99, 899.99, 'simple', 'draft', 'visible', 'Premium Android tablet with AMOLED display and S Pen included.', 'Ideal for note-taking, streaming, and multitasking on the go.', 'samsung', 'tablets', true, false],
            ['Sony WH-1000XM5', 'sony-wh-1000xm5', 'SNY-WH1000XM5', 349.99, 399.99, 'simple', 'draft', 'visible', 'Industry-leading noise-canceling wireless headphones.', 'Sony WH-1000XM5 with 30-hour battery, multipoint pairing, and premium comfort.', 'sony', 'audio-headphones', true, true],
            ['Nike Air Max 90', 'nike-air-max-90', 'NKE-AM90-M10', 130.00, 150.00, 'simple', 'draft', 'visible', 'Classic Nike sneaker with visible Air cushioning.', 'Iconic lifestyle shoe with leather and mesh upper. True to size fit.', 'nike', 'footwear', true, true],
            ['Adidas Ultraboost Light', 'adidas-ultraboost-light', 'ADI-UB-LIGHT-10', 190.00, 210.00, 'simple', 'draft', 'visible', 'Lightweight running shoe with responsive Boost midsole.', 'Adidas Ultraboost Light designed for daily training and all-day comfort.', 'adidas', 'footwear', true, false],
            ['LG OLED C4 65"', 'lg-oled-c4-65', 'LG-OLED-C4-65', 1799.00, 1999.00, 'simple', 'draft', 'visible', '65-inch 4K OLED TV with Dolby Vision and webOS.', 'Self-lit OLED pixels, 120Hz refresh rate, and four HDMI 2.1 ports for gaming.', 'lg', 'tvs-home-theater', true, true],
            ['Dell XPS 15', 'dell-xps-15', 'DEL-XPS15-I7', 1499.00, 1699.00, 'simple', 'draft', 'visible', 'Premium 15-inch laptop with InfinityEdge display.', 'Intel Core i7, 16GB RAM, 512GB SSD, and NVIDIA graphics for creators.', 'dell', 'laptops-computers', true, false],
            ['HP LaserJet Pro M404dn', 'hp-laserjet-pro-m404dn', 'HP-LJ-M404DN', 329.00, 379.00, 'simple', 'draft', 'visible', 'Fast monochrome laser printer for small offices.', 'Prints up to 38 ppm with automatic duplex and Ethernet connectivity.', 'hp', 'office-supplies', true, false],
            ['Canon EOS R8', 'canon-eos-r8', 'CAN-EOS-R8-BODY', 1499.00, 1599.00, 'simple', 'draft', 'visible', 'Full-frame mirrorless camera for photo and video.', '24.2MP sensor, 4K 60p video, and dual pixel autofocus in a compact body.', 'canon', 'cameras-photography', true, true],
            ['Dyson V15 Detect', 'dyson-v15-detect', 'DYS-V15-DETECT', 749.99, 799.99, 'simple', 'draft', 'visible', 'Cordless vacuum with laser dust detection.', 'Powerful suction, HEPA filtration, and intelligent piezo sensor.', 'dyson', 'appliances', true, false],
            ['Philips Sonicare 9900 Prestige', 'philips-sonicare-9900', 'PHI-SON9900', 299.99, 349.99, 'simple', 'draft', 'visible', 'Premium electric toothbrush with SenseIQ technology.', 'Removes up to 20x more plaque vs manual brushing with app guidance.', 'philips', 'beauty-personal-care', true, false],
            ['Bose QuietComfort Ultra Earbuds', 'bose-quietcomfort-ultra-earbuds', 'BOSE-QC-ULTRA', 299.00, 329.00, 'simple', 'draft', 'visible', 'Immersive audio with world-class noise cancellation.', 'Bose QuietComfort Ultra Earbuds with CustomTune and 24-hour total play time.', 'bose', 'audio-headphones', true, true],
            ['Levi\'s 501 Original Jeans', 'levis-501-original-jeans', 'LEV-501-32x32', 69.50, 79.50, 'simple', 'draft', 'visible', 'The original button-fly straight-leg denim jean.', 'Classic Levi\'s 501 in medium stonewash. Durable cotton denim.', 'levis', 'mens-clothing', true, false],
            ['IKEA KALLAX Shelf Unit', 'ikea-kallax-shelf-unit', 'IKE-KALLAX-44', 89.00, 99.00, 'simple', 'draft', 'visible', 'Versatile 4x4 cube storage for any room.', 'IKEA KALLAX shelving unit in white. Mix with inserts and baskets.', 'ikea', 'furniture', true, false],
            ['KitchenAid Artisan Stand Mixer', 'kitchenaid-artisan-stand-mixer', 'KIT-KSM150-RED', 449.99, 499.99, 'simple', 'draft', 'visible', '5-quart tilt-head mixer with 10 speeds.', 'KitchenAid Artisan in Empire Red with dough hook, whisk, and paddle.', 'kitchenaid', 'home-kitchen', true, true],
            ['Microsoft Surface Pro 9', 'microsoft-surface-pro-9', 'MS-SURF-PRO9-I5', 999.99, 1099.99, 'simple', 'draft', 'visible', '2-in-1 tablet and laptop with detachable keyboard support.', 'Intel Core i5, 8GB RAM, 256GB SSD, and Windows 11.', 'microsoft', 'laptops-computers', true, false],
            ['Google Pixel 8 Pro', 'google-pixel-8-pro', 'GGL-PXL8P-128', 899.00, 999.00, 'simple', 'draft', 'visible', 'AI-powered Android flagship with exceptional camera.', 'Google Pixel 8 Pro with Tensor G3, 50MP main sensor, and 7 years of updates.', 'google', 'smartphones', true, true],
        ];
    }
}
