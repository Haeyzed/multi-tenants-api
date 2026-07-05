<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuppliersImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'code',
            'description',
            'contact_name',
            'contact_email',
            'contact_phone',
            'website_url',
            'tax_id',
            'registration_number',
            'is_active',
        ];
    }

    /**
     * @return list<list<string|bool>>
     */
    public function array(): array
    {
        return [
            [
                'Acme Supplies',
                'ACME-001',
                'Primary procurement partner.',
                'Jane Doe',
                'jane@acme.test',
                '+2348000000000',
                'https://acme.test',
                'TAX-12345',
                'RC-98765',
                true,
            ],
        ];
    }
}
