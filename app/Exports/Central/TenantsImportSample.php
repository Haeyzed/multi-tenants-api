<?php

declare(strict_types=1);

namespace App\Exports\Central;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TenantsImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'slug',
            'email',
            'phone',
            'plan',
            'subdomain',
            'owner_name',
            'owner_email',
            'owner_phone',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            [
                'Acme Inc',
                'acme-inc',
                'contact@acme.inc',
                '+2348012345678',
                'starter',
                'acme',
                'John Doe',
                'owner@acme.inc',
                '+2348098765432',
            ],
        ];
    }
}
