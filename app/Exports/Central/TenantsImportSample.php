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
            ['Acme Retail', 'acme-retail', 'contact@acme-retail.com', '+2348012345001', 'starter', 'acme-retail', 'John Doe', 'john.doe@acme-retail.com', '+2348098765001'],
            ['GreenMart Stores', 'greenmart-stores', 'hello@greenmart.com', '+2348023456002', 'growth', 'greenmart', 'Sarah Johnson', 'sarah@greenmart.com', '+2348098765002'],
            ['TechHub Gadgets', 'techhub-gadgets', 'support@techhub.io', '+2348034567003', 'business', 'techhub', 'Emeka Nwosu', 'emeka@techhub.io', '+2348098765003'],
            ['Style Avenue', 'style-avenue', 'info@styleavenue.com', '+2348045678004', 'starter', 'styleavenue', 'Aisha Bello', 'aisha@styleavenue.com', '+2348098765004'],
            ['FreshBasket Foods', 'freshbasket-foods', 'orders@freshbasket.com', '+2348056789005', 'growth', 'freshbasket', 'Michael Adeyemi', 'michael@freshbasket.com', '+2348098765005'],
            ['FitNation Sports', 'fitnation-sports', 'team@fitnation.com', '+2348067890006', 'growth', 'fitnation', 'David Chen', 'david@fitnation.com', '+2348098765006'],
            ['BookNest Online', 'booknest-online', 'care@booknest.com', '+2348078901007', 'starter', 'booknest', 'Grace Mensah', 'grace@booknest.com', '+2348098765007'],
            ['HomeCraft Living', 'homecraft-living', 'sales@homecraft.com', '+233201234567', 'business', 'homecraft', 'Kwame Mensah', 'kwame@homecraft.com', '+233209876543'],
            ['PetPal Supplies', 'petpal-supplies', 'hello@petpal.com', '+2348089012008', 'starter', 'petpal', 'Funke Adeyemi', 'funke@petpal.com', '+2348098765008'],
            ['AutoParts Direct', 'autoparts-direct', 'service@autopartsdirect.com', '+254712345678', 'enterprise', 'autoparts', 'Amina Wanjiru', 'amina@autopartsdirect.com', '+254798765432'],
        ];
    }
}
