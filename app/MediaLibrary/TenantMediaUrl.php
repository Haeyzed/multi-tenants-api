<?php

declare(strict_types=1);

namespace App\MediaLibrary;

use App\Support\Tenancy\TenantDomain;
use Illuminate\Support\Facades\Storage;

/**
 * Build publicly accessible URLs for files stored on tenant-scoped disks.
 *
 * Tenant assets must be served from the tenant hostname via Stancl's
 * `/tenancy/assets/{path}` route — central APP_URL `/storage/...` links 404.
 *
 * Domains are stored as subdomains (e.g. "softmaxtech"); URLs always use the
 * full tenant API hostname (e.g. softmaxtech.multi-tenants-api.test).
 */
final class TenantMediaUrl
{
    public static function forPath(string $path, ?string $disk = null): string
    {
        $normalizedPath = ltrim($path, '/');

        if ($disk !== null && ! self::isLocalTenancyDisk($disk)) {
            return Storage::disk($disk)->url($normalizedPath);
        }

        $relativeUrl = '/tenancy/assets/'.$normalizedPath;

        if (! app()->runningInConsole() && str_contains(request()->getHost(), '.')) {
            return request()->getSchemeAndHttpHost().$relativeUrl;
        }

        $host = self::resolveTenantHostname();

        if ($host !== null) {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

            return "{$scheme}://{$host}{$relativeUrl}";
        }

        return asset($normalizedPath);
    }

    public static function isLocalTenancyDisk(string $disk): bool
    {
        return in_array($disk, ['public', 'local'], true);
    }

    private static function resolveTenantHostname(): ?string
    {
        if (! tenancy()->initialized) {
            return null;
        }

        $domain = tenant()->domains()
            ->where('is_primary', true)
            ->value('domain')
            ?? tenant()->domains()->value('domain');

        if ($domain === null || $domain === '') {
            return null;
        }

        return TenantDomain::qualify($domain);
    }
}
