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
            ['London UK Fulfillment', 'WH-LON-01', 'UK and Ireland order fulfillment.', '45 Thames Gateway Park', 'Unit 3', 'London', 'England', 'E16 2', 'GB', '+442071234567', 'london.fc@store.example.com', 'Oliver Hughes', 51.5074, -0.1278, true, false, 11],
            ['New York Metro DC', 'WH-NYC-01', 'US East Coast distribution center.', '200 Distribution Way', 'Building A', 'Newark', 'NJ', '07114', 'US', '+12015550100', 'newyork.dc@store.example.com', 'Sarah Mitchell', 40.7357, -74.1724, true, false, 12],
            ['Los Angeles West Coast DC', 'WH-LAX-01', 'West Coast fulfillment and cross-dock.', '8800 Commerce Drive', '', 'Commerce', 'CA', '90040', 'US', '+13105550101', 'lax.dc@store.example.com', 'James Rodriguez', 34.0005, -118.1595, true, false, 13],
            ['Dubai Middle East Hub', 'WH-DXB-01', 'Middle East and North Africa staging.', 'Jebel Ali Free Zone', 'Warehouse 14', 'Dubai', 'Dubai', '00000', 'AE', '+97143334455', 'dubai.hub@store.example.com', 'Ahmed Al-Rashid', 25.0070, 55.0830, true, false, 14],
            ['Johannesburg SA Hub', 'WH-JNB-01', 'Southern Africa regional warehouse.', '12 Industrial Crescent', 'Midrand', 'Johannesburg', 'Gauteng', '1685', 'ZA', '+27115550102', 'joburg.hub@store.example.com', 'Thabo Mbeki', -26.1076, 28.0567, true, false, 15],
            ['Singapore APAC Hub', 'WH-SIN-01', 'Asia-Pacific consolidation and export.', '8 Changi South Lane', '', 'Singapore', 'Singapore', '486119', 'SG', '+6565550103', 'singapore.hub@store.example.com', 'Wei Chen', 1.3340, 103.9630, true, false, 16],
            ['Frankfurt EU Central', 'WH-FRA-01', 'European Union central distribution.', '45 Logistik Park', 'Halle 7', 'Frankfurt', 'Hesse', '60549', 'DE', '+49695550104', 'frankfurt.dc@store.example.com', 'Hans Mueller', 50.0379, 8.5622, true, false, 17],
            ['Sydney Australia DC', 'WH-SYD-01', 'Australia and New Zealand fulfillment.', '22 Moorebank Avenue', '', 'Sydney', 'NSW', '2170', 'AU', '+61295550105', 'sydney.dc@store.example.com', 'Emma Wilson', -33.9249, 150.9250, true, false, 18],
            ['Toronto Canada DC', 'WH-YTO-01', 'Canadian national distribution center.', '100 Logistics Boulevard', '', 'Mississauga', 'ON', 'L5T 1', 'CA', '+19055550106', 'toronto.dc@store.example.com', 'Marcus Johnson', 43.6532, -79.3832, true, false, 19],
            ['Cold Storage Facility', 'WH-COLD-01', 'Temperature-controlled storage for sensitive goods.', '5 Refrigeration Park', 'Unit 2', 'Lagos', 'Lagos', '101003', 'NG', '+2348099990009', 'cold.storage@store.example.com', 'Ngozi Eze', 6.4600, 3.3900, true, false, 20],
        ];
    }
}
