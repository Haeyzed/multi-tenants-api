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
            ['Acme Industrial Supplies', 'SUP-ACME-001', 'Primary hardware and packaging supplier.', 'Jane Doe', 'jane.doe@acme-supplies.com', '+2348012345001', 'https://acme-supplies.com', 'TAX-NG-10001', 'RC-908712', true],
            ['GreenField Agro Ltd', 'SUP-GFA-002', 'Organic raw materials and food ingredients.', 'Samuel Okon', 'sales@greenfieldagro.com', '+2348023456002', 'https://greenfieldagro.com', 'TAX-NG-10002', 'RC-774521', true],
            ['Metro Textiles Nigeria', 'SUP-MTN-003', 'Fabric and apparel manufacturing partner.', 'Aisha Bello', 'orders@metrotextiles.ng', '+2348034567003', 'https://metrotextiles.ng', 'TAX-NG-10003', 'RC-661902', true],
            ['TechParts Distribution', 'SUP-TPD-004', 'Electronics components and accessories.', 'David Chen', 'procurement@techparts.io', '+2348045678004', 'https://techparts.io', 'TAX-NG-10004', 'RC-552341', true],
            ['Coastal Logistics Partners', 'SUP-CLP-005', 'Third-party freight and customs brokerage.', 'Grace Mensah', 'ops@coastallogistics.com', '+233201112233', 'https://coastallogistics.com', 'TAX-GH-20001', 'RC-GH-4412', true],
            ['Summit Home Goods', 'SUP-SHG-006', 'Kitchenware, decor, and household products.', 'Michael Adeyemi', 'wholesale@summithome.com', '+2348056789005', 'https://summithome.com', 'TAX-NG-10005', 'RC-449812', true],
            ['PureChem Industries', 'SUP-PCI-007', 'Cleaning and personal care formulations.', 'Ngozi Eze', 'supply@purechem.ng', '+2348067890006', 'https://purechem.ng', 'TAX-NG-10006', 'RC-338721', true],
            ['Atlas Print & Label', 'SUP-APL-008', 'Product labels, barcodes, and packaging print.', 'Kunle Ajayi', 'hello@atlasprint.ng', '+2348078901007', 'https://atlasprint.ng', 'TAX-NG-10007', 'RC-227610', true],
            ['Horizon Sports Wholesale', 'SUP-HSW-009', 'Sporting goods and fitness equipment.', 'Fatima Yusuf', 'b2b@horizonsports.com', '+2348089012008', 'https://horizonsports.com', 'TAX-NG-10008', 'RC-116509', true],
            ['Legacy Furniture Makers', 'SUP-LFM-010', 'Custom furniture and office fittings.', 'Patrick Osei', 'projects@legacyfurniture.com', '+233209998877', 'https://legacyfurniture.com', 'TAX-GH-20002', 'RC-GH-5521', true],
        ];
    }
}
