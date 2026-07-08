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
            ['Sarah Mitchell', 'sarah.mitchell@platform.example.com', '+14155550201', 'ChangeMe123!', true],
            ['James Rodriguez', 'james.rodriguez@platform.example.com', '+14155550202', 'ChangeMe123!', true],
            ['Emily Carter', 'emily.carter@platform.example.com', '+14155550203', 'ChangeMe123!', true],
            ['David Okonkwo', 'david.okonkwo@platform.example.com', '+2348012345001', 'ChangeMe123!', true],
            ['Aisha Bello', 'aisha.bello@platform.example.com', '+2348023456002', 'ChangeMe123!', true],
            ['Michael Adeyemi', 'michael.adeyemi@platform.example.com', '+2348034567003', 'ChangeMe123!', true],
            ['Grace Mensah', 'grace.mensah@platform.example.com', '+233201234567', 'ChangeMe123!', true],
            ['Oliver Hughes', 'oliver.hughes@platform.example.com', '+447911123456', 'ChangeMe123!', true],
            ['Priya Sharma', 'priya.sharma@platform.example.com', '+919876543210', 'ChangeMe123!', true],
            ['Kenji Tanaka', 'kenji.tanaka@platform.example.com', '+819012345678', 'ChangeMe123!', true],
            ['Hans Mueller', 'hans.mueller@platform.example.com', '+4915123456789', 'ChangeMe123!', true],
            ['Isabella Rossi', 'isabella.rossi@platform.example.com', '+393331234567', 'ChangeMe123!', true],
            ['Carlos Silva', 'carlos.silva@platform.example.com', '+5511987654321', 'ChangeMe123!', true],
            ['Amina Wanjiru', 'amina.wanjiru@platform.example.com', '+254712345678', 'ChangeMe123!', true],
            ['Wei Chen', 'wei.chen@platform.example.com', '+8613812345678', 'ChangeMe123!', true],
            ['Fatima Yusuf', 'fatima.yusuf@platform.example.com', '+2348067890006', 'ChangeMe123!', true],
            ['Patrick Osei', 'patrick.osei@platform.example.com', '+233209876543', 'ChangeMe123!', true],
            ['Lucas Dubois', 'lucas.dubois@platform.example.com', '+33612345678', 'ChangeMe123!', true],
            ['Hannah Schmidt', 'hannah.schmidt@platform.example.com', '+491601234567', 'ChangeMe123!', true],
            ['Marcus Johnson', 'marcus.johnson@platform.example.com', '+14155550220', 'ChangeMe123!', true],
        ];
    }
}
