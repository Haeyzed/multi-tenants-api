<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TaxZone;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for tax zone configuration.
 */
class TaxZonePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('tax.view');
    }

    public function view(TenantUser $user, TaxZone $taxZone): bool
    {
        return $user->can('tax.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('tax.create');
    }

    public function update(TenantUser $user, TaxZone $taxZone): bool
    {
        return $user->can('tax.update');
    }

    public function delete(TenantUser $user, TaxZone $taxZone): bool
    {
        return $user->can('tax.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('tax.delete');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('tax.update');
    }

    public function restore(TenantUser $user, TaxZone $taxZone): bool
    {
        return $user->can('tax.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('tax.delete');
    }

    public function forceDelete(TenantUser $user, TaxZone $taxZone): bool
    {
        return $user->can('tax.delete');
    }
}
