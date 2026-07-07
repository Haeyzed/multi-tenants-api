<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\ProductLabel;
use App\Models\Tenant\TenantUser;

class ProductLabelPolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('product-labels.view');
    }

    public function view(TenantUser $user, ProductLabel $productLabel): bool
    {
        return $user->can('product-labels.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('product-labels.create');
    }

    public function update(TenantUser $user, ProductLabel $productLabel): bool
    {
        return $user->can('product-labels.update');
    }

    public function updateAny(TenantUser $user): bool
    {
        return $user->can('product-labels.update');
    }

    public function delete(TenantUser $user, ProductLabel $productLabel): bool
    {
        return $user->can('product-labels.delete');
    }

    public function deleteAny(TenantUser $user): bool
    {
        return $user->can('product-labels.delete');
    }
}
