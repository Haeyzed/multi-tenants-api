<?php

declare(strict_types=1);

use App\Models\Central\Domain;
use App\Support\Tenancy\TenantDomain;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Domain::query()
            ->where('domain', 'not like', '%.%')
            ->orderBy('id')
            ->each(function (Domain $domain): void {
                $domain->update([
                    'domain' => TenantDomain::qualify($domain->domain),
                ]);
            });
    }

    public function down(): void
    {
        Domain::query()
            ->orderBy('id')
            ->each(function (Domain $domain): void {
                $subdomain = TenantDomain::subdomain($domain->domain);

                if ($subdomain !== $domain->domain) {
                    $domain->update(['domain' => $subdomain]);
                }
            });
    }
};
