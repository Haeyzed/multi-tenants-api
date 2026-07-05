<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TaxRule;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for tax rule configuration.
 */
class TaxRulePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('tax.view');
    }

    public function view(TenantUser $user, TaxRule $taxRule): bool
    {
        return $user->can('tax.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('tax.create');
    }

    public function update(TenantUser $user, TaxRule $taxRule): bool
    {
        return $user->can('tax.update');
    }

    public function delete(TenantUser $user, TaxRule $taxRule): bool
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
}
