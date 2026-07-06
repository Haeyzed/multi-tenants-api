<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Attribute;
use App\Models\Tenant\TenantUser;

class AttributePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('attributes.view');
    }

    public function view(TenantUser $user, Attribute $attribute): bool
    {
        return $user->can('attributes.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('attributes.create');
    }

    public function update(TenantUser $user, Attribute $attribute): bool
    {
        return $user->can('attributes.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('attributes.update');
    }

    public function delete(TenantUser $user, Attribute $attribute): bool
    {
        return $user->can('attributes.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('attributes.delete');
    }

    public function restore(TenantUser $user, Attribute $attribute): bool
    {
        return $user->can('attributes.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('attributes.delete');
    }

    public function forceDelete(TenantUser $user, Attribute $attribute): bool
    {
        return $user->can('attributes.delete');
    }
}
