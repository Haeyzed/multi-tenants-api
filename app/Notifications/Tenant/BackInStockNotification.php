<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\ProductStockAlert;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies customers that a subscribed variant is back in stock.
 */
class BackInStockNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(
        public Inventory $inventory,
        public ProductStockAlert $alert,
    ) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::BackInStock;
    }

    public function isAdminAlert(): bool
    {
        return false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inventory = $this->inventory->loadMissing(['variant.product']);
        $product = $inventory->variant?->product;

        return (new MailMessage)
            ->subject(($product?->name ?? 'A product').' is back in stock')
            ->line('Good news — an item you were waiting for is available again.')
            ->line('SKU: '.($inventory->variant?->sku ?? '—'))
            ->action('Shop now', url('/products/'.($product?->slug ?? '')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $inventory = $this->inventory->loadMissing(['variant.product']);

        return [
            'type' => NotificationEvent::BackInStock->value,
            'product_stock_alert_id' => $this->alert->id,
            'product_variant_id' => $inventory->product_variant_id,
            'product_id' => $inventory->variant?->product_id,
            'product_name' => $inventory->variant?->product?->name,
            'sku' => $inventory->variant?->sku,
        ];
    }
}
