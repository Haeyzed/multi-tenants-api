<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\TenantUser;

class CustomerPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.update');
    }

    public function delete(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('customers.delete');
    }

    public function restore(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('customers.delete');
    }

    public function forceDelete(TenantUser $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }
}
