<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Unit;

/**
 * Authorization rules for unit management.
 */
class UnitPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('units.view');
    }

    public function view(TenantUser $user, Unit $unit): bool
    {
        return $user->can('units.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('units.create');
    }

    public function update(TenantUser $user, Unit $unit): bool
    {
        return $user->can('units.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('units.update');
    }

    public function delete(TenantUser $user, Unit $unit): bool
    {
        return $user->can('units.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('units.delete');
    }
}
