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
            ['TechHub Electronics', 'techhub-electronics', 'hello@techhub-electronics.com', '+2348012345001', 'growth', 'techhub', 'Emeka Nwosu', 'emeka@techhub-electronics.com', '+2348098765001'],
            ['Style Avenue Fashion', 'style-avenue', 'info@styleavenue.com', '+2348023456002', 'starter', 'styleavenue', 'Aisha Bello', 'aisha@styleavenue.com', '+2348098765002'],
            ['GreenBasket Grocery', 'greenbasket', 'orders@greenbasket.com', '+2348034567003', 'growth', 'greenbasket', 'Michael Adeyemi', 'michael@greenbasket.com', '+2348098765003'],
            ['FitNation Sports', 'fitnation', 'team@fitnation.com', '+2348045678004', 'growth', 'fitnation', 'David Chen', 'david@fitnation.com', '+2348098765004'],
            ['HomeCraft Living', 'homecraft', 'sales@homecraft.com', '+233201234567', 'business', 'homecraft', 'Kwame Mensah', 'kwame@homecraft.com', '+233209876543'],
            ['BookNest Online', 'booknest', 'care@booknest.com', '+2348056789005', 'starter', 'booknest', 'Grace Mensah', 'grace@booknest.com', '+2348098765005'],
            ['AutoParts Direct', 'autoparts-direct', 'service@autopartsdirect.com', '+254712345678', 'enterprise', 'autoparts', 'Amina Wanjiru', 'amina@autopartsdirect.com', '+254798765432'],
            ['BeautyGlow Cosmetics', 'beautyglow', 'support@beautyglow.com', '+2348067890006', 'starter', 'beautyglow', 'Fatima Yusuf', 'fatima@beautyglow.com', '+2348098765006'],
            ['GadgetWorld Phones', 'gadgetworld', 'hello@gadgetworld.com', '+2348078901007', 'business', 'gadgetworld', 'Chinedu Eze', 'chinedu@gadgetworld.com', '+2348098765007'],
            ['PetPal Supplies', 'petpal', 'hello@petpal.com', '+2348089012008', 'starter', 'petpal', 'Funke Adeyemi', 'funke@petpal.com', '+2348098765008'],
            ['OfficePro Supplies', 'officepro', 'sales@officepro.com', '+14155550301', 'growth', 'officepro', 'Sarah Mitchell', 'sarah@officepro.com', '+14155550302'],
            ['KitchenKing Appliances', 'kitchenking', 'orders@kitchenking.com', '+14155550303', 'business', 'kitchenking', 'James Rodriguez', 'james@kitchenking.com', '+14155550304'],
            ['OutdoorGear Co', 'outdoorgear', 'info@outdoorgear.com', '+447911123456', 'growth', 'outdoorgear', 'Oliver Hughes', 'oliver@outdoorgear.com', '+447911123457'],
            ['KidsCorner Toys', 'kidscorner', 'hello@kidscorner.com', '+4915123456789', 'starter', 'kidscorner', 'Hannah Schmidt', 'hannah@kidscorner.com', '+491601234567'],
            ['PharmaCare Health', 'pharmacare', 'contact@pharmacare.com', '+919876543210', 'enterprise', 'pharmacare', 'Priya Sharma', 'priya@pharmacare.com', '+919876543211'],
            ['Artisan Coffee Roasters', 'artisan-coffee', 'wholesale@artisancoffee.com', '+5511987654321', 'starter', 'artisancoffee', 'Carlos Silva', 'carlos@artisancoffee.com', '+5511987654322'],
            ['LuxWatch Boutique', 'luxwatch', 'concierge@luxwatch.com', '+33612345678', 'business', 'luxwatch', 'Lucas Dubois', 'lucas@luxwatch.com', '+33612345679'],
            ['FarmFresh Produce', 'farmfresh', 'orders@farmfresh.com', '+819012345678', 'growth', 'farmfresh', 'Kenji Tanaka', 'kenji@farmfresh.com', '+819012345679'],
            ['SmartHome Hub', 'smarthome-hub', 'support@smarthomehub.com', '+8613812345678', 'business', 'smarthome', 'Wei Chen', 'wei@smarthomehub.com', '+8613812345679'],
            ['Vintage Vinyl Records', 'vintage-vinyl', 'shop@vintagevinyl.com', '+4915123456790', 'starter', 'vintagevinyl', 'Hans Mueller', 'hans@vintagevinyl.com', '+491601234568'],
        ];
    }
}
