<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for inventory adjustments.
 */
class InventoryAdjustmentPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('inventory-adjustments.view');
    }

    public function view(TenantUser $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory-adjustments.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('inventory-adjustments.create');
    }

    public function delete(TenantUser $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory-adjustments.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('inventory-adjustments.delete');
    }
}
