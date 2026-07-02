<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Enums\Central\DomainVerificationStatus;
use App\Support\Tenancy\TenantDomain;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * Domain attached to a tenant for subdomain or custom domain access.
 *
 * @property int $id
 * @property string $domain
 * @property string $tenant_id
 * @property bool $is_primary
 * @property DomainVerificationStatus $verification_status
 * @property string|null $verification_token
 * @property \Illuminate\Support\Carbon|null $verified_at
 */
class Domain extends BaseDomain
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verification_status' => DomainVerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === DomainVerificationStatus::Verified;
    }

    public function getFullDomainAttribute(): string
    {
        return TenantDomain::qualify($this->domain);
    }
}
