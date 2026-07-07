<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Models\Central\Tenant;
use App\Models\Tenant\BusinessSetting;
use App\Models\Tenant\EmailSetting;
use App\Models\Tenant\StoreSetting;

/**
 * Seeds default store settings from the central tenant record after provisioning.
 */
class SeedTenantSettings
{
    public function __construct(
        private readonly Tenant $tenant,
    )
    {
    }

    public function handle(): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenant->getKey());

        tenancy()->initialize($tenant);

        try {
            BusinessSetting::singleton()->fill([
                'business_name' => $tenant->name,
                'business_email' => $tenant->email,
                'business_phone' => $tenant->phone,
            ])->save();

            StoreSetting::singleton()->fill([
                'store_name' => $tenant->name,
                'contact_email' => $tenant->email,
                'contact_phone' => $tenant->phone,
            ])->save();

            EmailSetting::singleton()->fill([
                'sender_name' => $tenant->name,
                'sender_email' => $tenant->email,
            ])->save();
        } finally {
            tenancy()->end();
        }
    }
}
