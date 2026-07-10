<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum InventoryAdjustmentItemAction: string
{
    case Addition = 'addition';
    case Subtraction = 'subtraction';

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
            self::Addition => 'Addition (+)',
            self::Subtraction => 'Subtraction (-)',
        };
    }

    public function signedQuantity(int $quantity): int
    {
        return match ($this) {
            self::Addition => abs($quantity),
            self::Subtraction => -abs($quantity),
        };
    }
}
