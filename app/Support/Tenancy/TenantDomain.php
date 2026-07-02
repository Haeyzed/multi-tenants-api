<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

/**
 * Builds tenant hostnames for Stancl domain identification.
 *
 * Pattern: {subdomain}.{tenant_base_domain}
 * Example: softmaxtech.multi-tenants-api.test
 */
final class TenantDomain
{
    public static function suffix(): string
    {
        return (string) (config('tenancy.tenant_base_domain')
            ?? config('app.tenant_base_domain')
            ?? env('TENANT_BASE_DOMAIN', 'multi-tenants-api.test'));
    }

    /**
     * Turn a subdomain slug into the full host stored in domains.domain.
     */
    public static function qualify(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if ($domain === '' || str_contains($domain, '.')) {
            return $domain;
        }

        return "{$domain}.".self::suffix();
    }

    /**
     * Extract the subdomain slug from a qualified hostname.
     */
    public static function subdomain(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        $suffix = self::suffix();

        if (str_ends_with($hostname, ".{$suffix}")) {
            return substr($hostname, 0, -(strlen($suffix) + 1));
        }

        return $hostname;
    }
}
