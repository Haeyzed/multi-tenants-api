<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Inventory;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for inventory management.
 */
class InventoryPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(TenantUser $user, Inventory $inventory): bool
    {
        return $user->can('inventory.view');
    }

    public function update(TenantUser $user, Inventory $inventory): bool
    {
        return $user->can('inventory.manage');
    }

    public function manage(TenantUser $user): bool
    {
        return $user->can('inventory.manage');
    }
}
