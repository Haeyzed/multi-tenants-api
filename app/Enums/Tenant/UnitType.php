<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum UnitType: string
{
    case Weight = 'weight';
    case Length = 'length';
    case Volume = 'volume';
    case Area = 'area';
    case Count = 'count';

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
            self::Weight => 'Weight',
            self::Length => 'Length',
            self::Volume => 'Volume',
            self::Area => 'Area',
            self::Count => 'Count',
        };
    }
}
