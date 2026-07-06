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
            ['Apparel', 'apparel', 'Attributes for clothing and footwear products.', true, 1],
            ['Electronics', 'electronics', 'Technical specs for devices and accessories.', true, 2],
            ['Food & Beverage', 'food-beverage', 'Nutrition, ingredients, and shelf-life details.', true, 3],
            ['Furniture', 'furniture', 'Dimensions, materials, and assembly information.', true, 4],
            ['Beauty', 'beauty', 'Skin type, ingredients, and usage attributes.', true, 5],
            ['Sports Equipment', 'sports-equipment', 'Performance and safety specifications.', true, 6],
            ['Books', 'books', 'Author, format, and publication metadata.', true, 7],
            ['Automotive Parts', 'automotive-parts', 'Compatibility and fitment attributes.', true, 8],
            ['Home Appliances', 'home-appliances', 'Power, capacity, and installation details.', true, 9],
            ['Jewelry', 'jewelry', 'Metal type, stone, and sizing attributes.', true, 10],
        ];
    }
}
