<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Tag;
use App\Models\Tenant\TenantUser;

class TagPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('tags.view');
    }

    public function view(TenantUser $user, Tag $tag): bool
    {
        return $user->can('tags.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('tags.create');
    }

    public function update(TenantUser $user, Tag $tag): bool
    {
        return $user->can('tags.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('tags.update');
    }

    public function delete(TenantUser $user, Tag $tag): bool
    {
        return $user->can('tags.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('tags.delete');
    }
}
