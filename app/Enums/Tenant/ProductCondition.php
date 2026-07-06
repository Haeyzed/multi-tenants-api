<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum ProductCondition: string
{
    case New = 'new';
    case Refurbished = 'refurbished';
    case Used = 'used';
    case OpenBox = 'open_box';
    case Damaged = 'damaged';

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
            self::New => 'New',
            self::Refurbished => 'Refurbished',
            self::Used => 'Used',
            self::OpenBox => 'Open Box',
            self::Damaged => 'Damaged',
        };
    }
}
