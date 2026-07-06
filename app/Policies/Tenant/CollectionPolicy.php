<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Collection;
use App\Models\Tenant\TenantUser;

class CollectionPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(TenantUser $user, Collection $collection): bool
    {
        return $user->can('collections.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('collections.create');
    }

    public function update(TenantUser $user, Collection $collection): bool
    {
        return $user->can('collections.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('collections.update');
    }

    public function delete(TenantUser $user, Collection $collection): bool
    {
        return $user->can('collections.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('collections.delete');
    }

    public function restore(TenantUser $user, Collection $collection): bool
    {
        return $user->can('collections.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('collections.delete');
    }

    public function forceDelete(TenantUser $user, Collection $collection): bool
    {
        return $user->can('collections.delete');
    }
}
