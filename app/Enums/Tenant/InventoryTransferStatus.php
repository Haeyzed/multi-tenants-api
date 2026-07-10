<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum InventoryTransferStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Completed = 'completed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Completed => 'Completed',
        };
    }

    public function appliesStockMovement(): bool
    {
        return $this === self::Sent || $this === self::Completed;
    }
}
