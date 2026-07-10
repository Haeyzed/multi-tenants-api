<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum InventoryAdjustmentStatus: string
{
    case Posted = 'posted';
    case Cancelled = 'cancelled';

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
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }
}
