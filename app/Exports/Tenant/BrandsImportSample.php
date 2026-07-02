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
            [
                'Acme',
                'acme',
                'Premium outdoor gear.',
                true,
                '',
                '',
                'Acme Brand',
                'Shop Acme products.',
                'https://acme.example.com',
                1,
            ],
        ];
    }
}
