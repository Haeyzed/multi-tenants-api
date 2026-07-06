<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\AttributeSet;
use App\Models\Tenant\TenantUser;

class AttributeSetPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('attribute-sets.view');
    }

    public function view(TenantUser $user, AttributeSet $attributeSet): bool
    {
        return $user->can('attribute-sets.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('attribute-sets.create');
    }

    public function update(TenantUser $user, AttributeSet $attributeSet): bool
    {
        return $user->can('attribute-sets.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('attribute-sets.update');
    }

    public function delete(TenantUser $user, AttributeSet $attributeSet): bool
    {
        return $user->can('attribute-sets.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('attribute-sets.delete');
    }
}
