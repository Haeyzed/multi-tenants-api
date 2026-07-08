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
            ['Ingram Micro', 'SUP-INGRAM-001', 'Global distributor of IT hardware, software, and cloud services.', 'Sarah Mitchell', 'partners@ingrammicro.com', '+1-800-456-8000', 'https://www.ingrammicro.com', 'US-94-1234567', 'RC-ING-001', true],
            ['Tech Data Corporation', 'SUP-TECHDATA-002', 'Wholesale distributor of technology products and solutions.', 'James Rodriguez', 'b2b@techdata.com', '+1-800-237-8282', 'https://www.techdata.com', 'US-59-2345678', 'RC-TD-002', true],
            ['Foxconn Technology', 'SUP-FOXCONN-003', 'Electronics manufacturing and assembly partner.', 'Wei Chen', 'supply@foxconn.com', '+886-2-2268-3466', 'https://www.foxconn.com', 'TW-12345678', 'RC-FC-003', true],
            ['Flex Ltd', 'SUP-FLEX-004', 'Design and manufacturing for consumer electronics.', 'Anita Kapoor', 'procurement@flex.com', '+65-6877-4883', 'https://www.flex.com', 'SG-201234567K', 'RC-FLX-004', true],
            ['Nike Inc. Wholesale', 'SUP-NIKE-005', 'Official Nike footwear and apparel distribution.', 'Marcus Johnson', 'wholesale@nike.com', '+1-800-344-6453', 'https://www.nike.com', 'US-93-0584541', 'RC-NKE-005', true],
            ['Samsung Electronics', 'SUP-SAMSUNG-006', 'Consumer electronics and mobile device supply.', 'Ji-hoon Park', 'b2b@samsung.com', '+82-2-2255-0114', 'https://www.samsung.com', 'KR-123-45-67890', 'RC-SSG-006', true],
            ['IKEA Supply AG', 'SUP-IKEA-007', 'Flat-pack furniture and home furnishing wholesale.', 'Erik Lindstrom', 'trade@ikea.com', '+46-8-508-52-000', 'https://www.ikea.com', 'SE-5560747569', 'RC-IKEA-007', true],
            ['Procter & Gamble', 'SUP-PG-008', 'Beauty, grooming, and household consumer goods.', 'Lisa Thompson', 'trade@pg.com', '+1-513-983-1100', 'https://www.pg.com', 'US-31-0411980', 'RC-PG-008', true],
            ['Canon Inc.', 'SUP-CANON-009', 'Cameras, printers, and imaging equipment supply.', 'Yuki Tanaka', 'dealer@canon.com', '+81-3-5482-1111', 'https://www.canon.com', 'JP-1234567890123', 'RC-CAN-009', true],
            ['Dyson Ltd', 'SUP-DYSON-010', 'Vacuum cleaners, air treatment, and personal care devices.', 'Oliver Hughes', 'trade@dyson.com', '+44-800-298-0298', 'https://www.dyson.com', 'GB-10870194', 'RC-DYS-010', true],
            ['Levi Strauss & Co.', 'SUP-LEVIS-011', 'Denim and casual apparel wholesale.', 'Rachel Green', 'wholesale@levi.com', '+1-415-501-6000', 'https://www.levi.com', 'US-94-0495530', 'RC-LEV-011', true],
            ['KitchenAid / Whirlpool', 'SUP-KITCHENAID-012', 'Kitchen appliances and small electrics.', 'Tom Baker', 'dealer@kitchenaid.com', '+1-800-422-1230', 'https://www.kitchenaid.com', 'US-38-1498030', 'RC-KA-012', true],
            ['Bose Corporation', 'SUP-BOSE-013', 'Premium audio equipment and accessories.', 'Emily Carter', 'b2b@bose.com', '+1-800-379-2073', 'https://www.bose.com', 'US-04-2745157', 'RC-BOSE-013', true],
            ['Patagonia Works', 'SUP-PATAGONIA-014', 'Outdoor apparel and gear distribution.', 'Chris Morales', 'trade@patagonia.com', '+1-800-638-6464', 'https://www.patagonia.com', 'US-95-3679620', 'RC-PAT-014', true],
            ['HP Inc.', 'SUP-HP-015', 'Printers, PCs, and office technology supply.', 'David Okonkwo', 'partners@hp.com', '+1-650-857-1501', 'https://www.hp.com', 'US-94-1081436', 'RC-HP-015', true],
            ['Dell Technologies', 'SUP-DELL-016', 'Computers, servers, and enterprise hardware.', 'Priya Sharma', 'channel@dell.com', '+1-800-999-3355', 'https://www.dell.com', 'US-75-1574680', 'RC-DELL-016', true],
            ['LG Electronics', 'SUP-LG-017', 'TVs, appliances, and display products.', 'Min-jun Lee', 'b2b@lg.com', '+82-2-3777-1114', 'https://www.lg.com', 'KR-110-81-39970', 'RC-LG-017', true],
            ['Adidas AG', 'SUP-ADIDAS-018', 'Sportswear and footwear wholesale partner.', 'Hans Mueller', 'trade@adidas.com', '+49-9132-84-0', 'https://www.adidas.com', 'DE-129274202', 'RC-ADI-018', true],
            ['Sony Corporation', 'SUP-SONY-019', 'Consumer electronics, gaming, and audio supply.', 'Kenji Watanabe', 'dealer@sony.com', '+81-3-6748-2111', 'https://www.sony.com', 'JP-4988010001234', 'RC-SNY-019', true],
            ['Uniqlo / Fast Retailing', 'SUP-UNIQLO-020', 'Casual apparel and basics wholesale.', 'Hana Sato', 'b2b@uniqlo.com', '+81-3-6885-5500', 'https://www.uniqlo.com', 'JP-2130001050000', 'RC-UNI-020', true],
        ];
    }
}
