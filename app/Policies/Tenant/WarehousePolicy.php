<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Warehouse;

/**
 * Authorization rules for warehouse management.
 */
class WarehousePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('warehouses.view');
    }

    public function view(TenantUser $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('warehouses.create');
    }

    public function update(TenantUser $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('warehouses.update');
    }

    public function delete(TenantUser $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('warehouses.delete');
    }

    public function restore(TenantUser $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('warehouses.delete');
    }

    public function forceDelete(TenantUser $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }
}
