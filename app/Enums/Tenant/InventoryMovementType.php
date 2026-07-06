<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum InventoryMovementType: string
{
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';
    case Transfer = 'transfer';
    case Receipt = 'receipt';
    case Reservation = 'reservation';
    case Release = 'release';
    case Damage = 'damage';
    case Shrinkage = 'shrinkage';
    case Restock = 'restock';
    case Initial = 'initial';

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
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Transfer => 'Transfer',
            self::Receipt => 'Receipt',
            self::Reservation => 'Reservation',
            self::Release => 'Release',
            self::Damage => 'Damage',
            self::Shrinkage => 'Shrinkage',
            self::Restock => 'Restock',
            self::Initial => 'Initial Stock',
        };
    }

    public function increasesStock(): bool
    {
        return match ($this) {
            self::Return, self::Receipt, self::Release, self::Restock, self::Initial => true,
            self::Adjustment => false,
            default => false,
        };
    }
}
