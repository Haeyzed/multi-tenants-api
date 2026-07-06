<?php

declare(strict_types=1);

namespace App\Exports\Central;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersImportSample implements FromArray, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'name',
            'email',
            'phone',
            'password',
            'is_active',
        ];
    }

    /**
     * @return list<list<string|bool>>
     */
    public function array(): array
    {
        return [
            ['Jane Admin', 'jane.admin@example.com', '+2348012345001', 'ChangeMe123!', true],
            ['Michael Ops', 'michael.ops@example.com', '+2348023456002', 'ChangeMe123!', true],
            ['Aisha Support', 'aisha.support@example.com', '+2348034567003', 'ChangeMe123!', true],
            ['David Finance', 'david.finance@example.com', '+2348045678004', 'ChangeMe123!', true],
            ['Grace Marketing', 'grace.marketing@example.com', '+233201234567', 'ChangeMe123!', true],
            ['Samuel Engineering', 'samuel.engineering@example.com', '+2348056789005', 'ChangeMe123!', true],
            ['Fatima Compliance', 'fatima.compliance@example.com', '+2348067890006', 'ChangeMe123!', true],
            ['Patrick Sales', 'patrick.sales@example.com', '+233209876543', 'ChangeMe123!', true],
            ['Chinedu Product', 'chinedu.product@example.com', '+2348078901007', 'ChangeMe123!', true],
            ['Amina Success', 'amina.success@example.com', '+254712345678', 'ChangeMe123!', true],
        ];
    }
}
