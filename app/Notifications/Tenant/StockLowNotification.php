<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use App\Contracts\Tenant\TenantNotification;
use App\Enums\Tenant\NotificationEvent;
use App\Models\Tenant\Inventory;
use App\Notifications\Tenant\Concerns\UsesTenantNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts inventory managers that a variant has reached low stock.
 */
class StockLowNotification extends Notification implements ShouldQueue, TenantNotification
{
    use Queueable;
    use UsesTenantNotificationSettings;

    public function __construct(public Inventory $inventory) {}

    public function notificationEvent(): NotificationEvent
    {
        return NotificationEvent::StockLow;
    }

    public function isAdminAlert(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inventory = $this->inventory->loadMissing(['variant.product', 'warehouse']);
        $variant = $inventory->variant;
        $product = $variant?->product;

        return (new MailMessage)
            ->subject('Low stock alert: '.($variant?->sku ?? 'Variant'))
            ->line(($product?->name ?? 'A product').' is running low at '.$inventory->warehouse?->name.'.')
            ->line('Available quantity: '.$inventory->availableQuantity())
            ->line('Reorder level: '.($inventory->reorder_level ?? '—'))
            ->action('View inventory', url('/admin/inventory'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $inventory = $this->inventory->loadMissing(['variant.product', 'warehouse']);

        return [
            'type' => NotificationEvent::StockLow->value,
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'warehouse_id' => $inventory->warehouse_id,
            'available_quantity' => $inventory->availableQuantity(),
            'reorder_level' => $inventory->reorder_level,
            'sku' => $inventory->variant?->sku,
            'product_name' => $inventory->variant?->product?->name,
            'warehouse_name' => $inventory->warehouse?->name,
        ];
    }
}
