<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttributeSetsImportSample implements FromArray, WithHeadings
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
            ['Smartphones', 'smartphones', 'Storage, color, screen size, and connectivity for mobile phones.', true, 1],
            ['Laptops', 'laptops', 'RAM, storage, screen size, and OS for notebooks.', true, 2],
            ['Televisions', 'televisions', 'Screen size, resolution, refresh rate, and smart features.', true, 3],
            ['Footwear', 'footwear', 'Size, color, material, and fit for shoes.', true, 4],
            ['Apparel', 'apparel', 'Size, color, fit, and fabric for clothing.', true, 5],
            ['Audio', 'audio', 'Connectivity, battery life, and noise cancellation specs.', true, 6],
            ['Cameras', 'cameras', 'Sensor type, megapixels, lens mount, and video resolution.', true, 7],
            ['Kitchen Appliances', 'kitchen-appliances', 'Capacity, power, voltage, and material.', true, 8],
            ['Furniture', 'furniture', 'Dimensions, material, color, and assembly requirements.', true, 9],
            ['Beauty', 'beauty', 'Skin type, ingredients, volume, and usage instructions.', true, 10],
            ['Sports Equipment', 'sports-equipment', 'Weight, size, material, and skill level.', true, 11],
            ['Office Printers', 'office-printers', 'Print speed, connectivity, and toner compatibility.', true, 12],
            ['Vacuum Cleaners', 'vacuum-cleaners', 'Suction power, battery runtime, and filtration.', true, 13],
            ['Watches', 'watches', 'Case size, band material, water resistance, and movement.', true, 14],
            ['Tablets', 'tablets', 'Storage, display size, stylus support, and cellular option.', true, 15],
            ['Gaming Consoles', 'gaming-consoles', 'Storage, edition, region, and bundled accessories.', true, 16],
            ['Baby Gear', 'baby-gear', 'Age range, weight limit, and safety certifications.', true, 17],
            ['Automotive Accessories', 'automotive-accessories', 'Vehicle compatibility, fitment, and material.', true, 18],
            ['Books & Media', 'books-media', 'Author, format, language, and publication year.', true, 19],
            ['Food & Grocery', 'food-grocery', 'Weight, flavor, allergens, and shelf life.', true, 20],
        ];
    }
}
