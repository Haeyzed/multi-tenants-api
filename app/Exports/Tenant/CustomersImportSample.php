<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'customer_group_id',
            'date_of_birth',
            'gender',
            'is_active',
        ];
    }

    /**
     * @return list<list<string|bool|int>>
     */
    public function array(): array
    {
        return [
            [
                'Jane',
                'Doe',
                'jane@example.com',
                '+1234567890',
                '',
                '1990-01-15',
                'female',
                true,
            ],
        ];
    }
}
