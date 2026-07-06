<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when variant inventory falls to or below its reorder level.
 */
class StockLow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Inventory $inventory) {}
}
