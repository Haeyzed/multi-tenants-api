<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehousesImportSample implements FromArray, WithHeadings
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
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'email',
            'manager_name',
            'latitude',
            'longitude',
            'is_active',
            'is_primary',
            'sort_order',
        ];
    }

    /**
     * @return list<list<string|bool|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Lagos Main Distribution Center', 'WH-LAG-01', 'Primary fulfillment hub for southwest Nigeria.', '14 Warehouse Road', 'Apapa Industrial Estate', 'Lagos', 'Lagos', '101001', 'NG', '+2348011110001', 'lagos.main@store.example.com', 'Adaeze Okafor', 6.5244, 3.3792, true, true, 1],
            ['Abuja Regional Warehouse', 'WH-ABJ-01', 'Central Nigeria distribution point.', '22 Katampe Road', 'Suite 4', 'Abuja', 'FCT', '900001', 'NG', '+2348022220002', 'abuja.regional@store.example.com', 'Emeka Nwosu', 9.0765, 7.3986, true, false, 2],
            ['Port Harcourt Depot', 'WH-PHC-01', 'South-south pickup and bulk storage.', '8 Trans Amadi Road', '', 'Port Harcourt', 'Rivers', '500001', 'NG', '+2348033330003', 'ph.depot@store.example.com', 'Blessing Tamuno', 4.8156, 7.0498, true, false, 3],
            ['Kano Northern Hub', 'WH-KAN-01', 'Northern region stock consolidation.', '45 Bompai Road', '', 'Kano', 'Kano', '700001', 'NG', '+2348044440004', 'kano.hub@store.example.com', 'Yusuf Ibrahim', 12.0022, 8.5920, true, false, 4],
            ['Ibadan Fulfillment Center', 'WH-IBD-01', 'Handles Oyo and surrounding deliveries.', '3 Oluyole Estate', 'Block B', 'Ibadan', 'Oyo', '200001', 'NG', '+2348055550005', 'ibadan.fc@store.example.com', 'Funke Adeyemi', 7.3775, 3.9470, true, false, 5],
            ['Enugu Cross-Dock Facility', 'WH-ENU-01', 'Fast cross-docking for eastern states.', '11 Ogui Road', '', 'Enugu', 'Enugu', '400001', 'NG', '+2348066660006', 'enugu.crossdock@store.example.com', 'Chinedu Eze', 6.4584, 7.5464, true, false, 6],
            ['Benin City Storage', 'WH-BNI-01', 'Midwest inventory buffer warehouse.', '7 Sapele Road', '', 'Benin City', 'Edo', '300001', 'NG', '+2348077770007', 'benin.storage@store.example.com', 'Osaro Igbinosa', 6.3350, 5.6037, true, false, 7],
            ['Accra Ghana Export Hub', 'WH-ACC-01', 'West Africa export staging warehouse.', '19 Spintex Road', '', 'Accra', 'Greater Accra', 'GA-123', 'GH', '+233201234567', 'accra.export@store.example.com', 'Kwame Mensah', 5.6037, -0.1870, true, false, 8],
            ['Nairobi East Africa Hub', 'WH-NBO-01', 'East Africa regional distribution.', '55 Mombasa Road', 'Warehouse 12', 'Nairobi', 'Nairobi', '00100', 'KE', '+254712345678', 'nairobi.hub@store.example.com', 'Amina Wanjiru', -1.2921, 36.8219, true, false, 9],
            ['Returns Processing Center', 'WH-RTN-01', 'Dedicated returns inspection and restocking.', '2 Refurb Lane', 'Unit 7', 'Lagos', 'Lagos', '101002', 'NG', '+2348088880008', 'returns@store.example.com', 'Tolu Bakare', 6.4550, 3.3941, true, false, 10],
        ];
    }
}
