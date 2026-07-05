<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Supplier;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for supplier management.
 */
class SupplierPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('suppliers.view');
    }

    public function view(TenantUser $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('suppliers.create');
    }

    public function update(TenantUser $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('suppliers.update');
    }

    public function delete(TenantUser $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('suppliers.delete');
    }

    public function restore(TenantUser $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('suppliers.delete');
    }

    public function forceDelete(TenantUser $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete');
    }
}
