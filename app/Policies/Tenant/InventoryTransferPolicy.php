<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\InventoryTransfer;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for inventory transfers.
 */
class InventoryTransferPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('inventory-transfers.view');
    }

    public function view(TenantUser $user, InventoryTransfer $inventoryTransfer): bool
    {
        return $user->can('inventory-transfers.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('inventory-transfers.create');
    }

    public function delete(TenantUser $user, InventoryTransfer $inventoryTransfer): bool
    {
        return $user->can('inventory-transfers.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('inventory-transfers.delete');
    }
}
