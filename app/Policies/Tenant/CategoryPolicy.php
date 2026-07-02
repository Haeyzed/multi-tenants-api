<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for category management.
 */
class CategoryPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(TenantUser $user, Category $category): bool
    {
        return $user->can('categories.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(TenantUser $user, Category $category): bool
    {
        return $user->can('categories.update');
    }

    public function delete(TenantUser $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('categories.delete');
    }

    public function restore(TenantUser $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }

    public function restoreAny(TenantUser $user): bool
    {
        return $user->can('categories.delete');
    }

    public function forceDelete(TenantUser $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }
}
