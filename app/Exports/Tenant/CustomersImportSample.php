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
            ['Jane', 'Doe', 'jane.doe@gmail.com', '+2348012345001', '', '1990-03-14', 'female', true],
            ['Michael', 'Adeyemi', 'michael.adeyemi@outlook.com', '+2348023456002', '', '1988-07-22', 'male', true],
            ['Aisha', 'Bello', 'aisha.bello@yahoo.com', '+2348034567003', '', '1995-11-05', 'female', true],
            ['David', 'Okoro', 'david.okoro@gmail.com', '+2348045678004', '', '1992-01-30', 'male', true],
            ['Grace', 'Mensah', 'grace.mensah@icloud.com', '+233201234567', '', '1993-09-18', 'female', true],
            ['Samuel', 'Nwachukwu', 'samuel.nwachukwu@gmail.com', '+2348056789005', '', '1987-12-02', 'male', true],
            ['Fatima', 'Yusuf', 'fatima.yusuf@hotmail.com', '+2348067890006', '', '1998-04-27', 'female', true],
            ['Patrick', 'Osei', 'patrick.osei@gmail.com', '+233209876543', '', '1991-06-11', 'male', true],
            ['Chinedu', 'Eze', 'chinedu.eze@yahoo.com', '+2348078901007', '', '1989-08-09', 'male', true],
            ['Amina', 'Wanjiru', 'amina.wanjiru@gmail.com', '+254712345678', '', '1996-02-16', 'female', true],
            ['Emily', 'Johnson', 'emily.johnson@gmail.com', '+14155550101', '', '1994-05-20', 'female', true],
            ['James', 'Wilson', 'james.wilson@outlook.com', '+14155550102', '', '1985-10-03', 'male', true],
            ['Sophia', 'Martinez', 'sophia.martinez@yahoo.com', '+34612345678', '', '1997-12-25', 'female', true],
            ['Oliver', 'Brown', 'oliver.brown@gmail.com', '+447911123456', '', '1990-07-08', 'male', true],
            ['Priya', 'Sharma', 'priya.sharma@gmail.com', '+919876543210', '', '1993-03-30', 'female', true],
            ['Kenji', 'Tanaka', 'kenji.tanaka@icloud.com', '+819012345678', '', '1988-11-14', 'male', true],
            ['Isabella', 'Rossi', 'isabella.rossi@gmail.com', '+393331234567', '', '1999-01-07', 'female', true],
            ['Lucas', 'Dubois', 'lucas.dubois@outlook.com', '+33612345678', '', '1992-08-19', 'male', true],
            ['Hannah', 'Schmidt', 'hannah.schmidt@yahoo.com', '+4915123456789', '', '1995-06-02', 'female', true],
            ['Carlos', 'Silva', 'carlos.silva@gmail.com', '+5511987654321', '', '1986-04-11', 'male', true],
        ];
    }
}
