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
            [
                'Electronics',
                'electronics',
                'Consumer electronics and gadgets.',
                'Electronics',
                'Browse electronics.',
                '',
                true,
                1,
                '',
                '',
                '#2563eb',
                'laptop',
            ],
        ];
    }
}
