<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a variant becomes available after being out of stock.
 */
class VariantBackInStock
{
    use Dispatchable, SerializesModels;

    public function __construct(public Inventory $inventory) {}
}
