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
            ['Wireless Bluetooth Earbuds', 'wireless-bluetooth-earbuds', 'SKU-AUD-001', 49.99, 69.99, 'simple', 'draft', 'visible', 'Compact earbuds with active noise cancellation.', 'Premium wireless earbuds with 30-hour battery life and USB-C charging case.', '', 'electronics', true, true],
            ['Stainless Steel Water Bottle', 'stainless-steel-water-bottle', 'SKU-HOM-002', 24.50, 29.99, 'simple', 'draft', 'visible', 'Insulated 750ml bottle keeps drinks cold for 24 hours.', 'Double-wall vacuum insulated bottle, BPA-free, leak-proof lid.', '', 'home-kitchen', true, false],
            ['Organic Cotton T-Shirt', 'organic-cotton-t-shirt', 'SKU-APP-003', 19.99, 24.99, 'simple', 'draft', 'visible', 'Soft unisex tee made from 100% organic cotton.', 'Breathable everyday t-shirt available in multiple colors.', '', 'clothing', true, false],
            ['Running Shoes Pro', 'running-shoes-pro', 'SKU-SPT-004', 89.00, 119.00, 'simple', 'draft', 'visible', 'Lightweight trainers for road and treadmill running.', 'Responsive cushioning, breathable mesh upper, durable rubber outsole.', '', 'sports', true, true],
            ['LED Desk Lamp', 'led-desk-lamp', 'SKU-OFF-005', 34.99, 44.99, 'simple', 'draft', 'visible', 'Adjustable lamp with warm and cool light modes.', 'Touch controls, USB charging port, energy-efficient LED panel.', '', 'office', true, false],
            ['Natural Face Moisturizer', 'natural-face-moisturizer', 'SKU-BEA-006', 27.00, 32.00, 'simple', 'draft', 'visible', 'Daily hydrating cream for all skin types.', 'Formulated with aloe vera, shea butter, and vitamin E.', '', 'beauty', true, false],
            ['Portable Power Bank 20000mAh', 'portable-power-bank-20000mah', 'SKU-ELC-007', 39.99, 49.99, 'simple', 'draft', 'visible', 'Fast-charging power bank for phones and tablets.', 'Dual USB outputs, LED charge indicator, airline-safe capacity.', '', 'electronics', true, false],
            ['Ceramic Coffee Mug Set', 'ceramic-coffee-mug-set', 'SKU-HOM-008', 18.75, 22.00, 'simple', 'draft', 'visible', 'Set of 4 stackable mugs for home or office.', 'Microwave and dishwasher safe ceramic mugs with matte finish.', '', 'home-kitchen', true, false],
            ['Yoga Mat Premium', 'yoga-mat-premium', 'SKU-SPT-009', 32.50, 39.99, 'simple', 'draft', 'visible', 'Non-slip mat with extra thickness for comfort.', 'Eco-friendly TPE material, includes carrying strap.', '', 'sports', true, false],
            ['Business Notebook A5', 'business-notebook-a5', 'SKU-OFF-010', 8.99, 11.99, 'simple', 'draft', 'visible', 'Hardcover ruled notebook for meetings and notes.', '120gsm paper, ribbon bookmark, elastic closure.', '', 'office', true, false],
        ];
    }
}
