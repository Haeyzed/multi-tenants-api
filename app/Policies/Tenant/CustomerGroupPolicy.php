<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CustomerGroup;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for customer groups.
 */
class CustomerGroupPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(TenantUser $user, CustomerGroup $group): bool
    {
        return $user->can('customers.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('customers.manage');
    }

    public function update(TenantUser $user, CustomerGroup $group): bool
    {
        return $user->can('customers.manage');
    }

    public function delete(TenantUser $user, CustomerGroup $group): bool
    {
        return $user->can('customers.manage');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('customers.manage');
    }

    public function restore(TenantUser $user, CustomerGroup $group): bool
    {
        return $user->can('customers.manage');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('customers.manage');
    }

    public function forceDelete(TenantUser $user, CustomerGroup $group): bool
    {
        return $user->can('customers.manage');
    }
}
