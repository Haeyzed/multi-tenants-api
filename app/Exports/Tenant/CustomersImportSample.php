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
            ['Jane', 'Doe', 'jane.doe@example.com', '+2348012345001', '', '1990-03-14', 'female', true],
            ['Michael', 'Adeyemi', 'michael.adeyemi@example.com', '+2348023456002', '', '1988-07-22', 'male', true],
            ['Aisha', 'Bello', 'aisha.bello@example.com', '+2348034567003', '', '1995-11-05', 'female', true],
            ['David', 'Okoro', 'david.okoro@example.com', '+2348045678004', '', '1992-01-30', 'male', true],
            ['Grace', 'Mensah', 'grace.mensah@example.com', '+233201234567', '', '1993-09-18', 'female', true],
            ['Samuel', 'Nwachukwu', 'samuel.nwachukwu@example.com', '+2348056789005', '', '1987-12-02', 'male', true],
            ['Fatima', 'Yusuf', 'fatima.yusuf@example.com', '+2348067890006', '', '1998-04-27', 'female', true],
            ['Patrick', 'Osei', 'patrick.osei@example.com', '+233209876543', '', '1991-06-11', 'male', true],
            ['Chinedu', 'Eze', 'chinedu.eze@example.com', '+2348078901007', '', '1989-08-09', 'male', true],
            ['Amina', 'Wanjiru', 'amina.wanjiru@example.com', '+254712345678', '', '1996-02-16', 'female', true],
        ];
    }
}
