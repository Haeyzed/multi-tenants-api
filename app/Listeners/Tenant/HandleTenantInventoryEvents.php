<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\StockLow;
use App\Events\Tenant\VariantBackInStock;
use App\Models\Tenant\ProductStockAlert;
use App\Notifications\Tenant\BackInStockNotification;
use App\Notifications\Tenant\StockLowNotification;
use App\Services\Tenant\NotificationDispatchService;

/**
 * Sends inventory-related staff and customer notifications.
 */
class HandleTenantInventoryEvents
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {}

    public function handleStockLow(StockLow $event): void
    {
        $inventory = $event->inventory->loadMissing(['variant.product', 'warehouse']);

        $managers = $this->notificationDispatchService->staffWithPermission('inventory.manage');

        $this->notificationDispatchService->notifyUsers(
            $managers,
            new StockLowNotification($inventory),
        );
    }

    public function handleVariantBackInStock(VariantBackInStock $event): void
    {
        $inventory = $event->inventory->loadMissing(['variant.product']);

        $alerts = ProductStockAlert::query()
            ->pending()
            ->where('product_variant_id', $inventory->product_variant_id)
            ->get();

        foreach ($alerts as $alert) {
            $alert->loadMissing('customer.user');
            $notification = new BackInStockNotification($inventory, $alert);

            if ($alert->customer?->user) {
                $this->notificationDispatchService->notifyUser(
                    $alert->customer->user,
                    $notification,
                );
            } else {
                $this->notificationDispatchService->notifyMail(
                    $alert->email,
                    $notification,
                );
            }

            $alert->update([
                'is_notified' => true,
                'notified_at' => now(),
            ]);
        }
    }
}
