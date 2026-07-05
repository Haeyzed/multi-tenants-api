<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for tax rate configuration.
 */
class TaxRatePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('tax.view');
    }

    public function view(TenantUser $user, TaxRate $taxRate): bool
    {
        return $user->can('tax.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('tax.create');
    }

    public function update(TenantUser $user, TaxRate $taxRate): bool
    {
        return $user->can('tax.update');
    }

    public function delete(TenantUser $user, TaxRate $taxRate): bool
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

    public function restore(TenantUser $user, TaxRate $taxRate): bool
    {
        return $user->can('tax.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('tax.delete');
    }

    public function forceDelete(TenantUser $user, TaxRate $taxRate): bool
    {
        return $user->can('tax.delete');
    }
}
