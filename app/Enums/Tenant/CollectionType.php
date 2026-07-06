<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum CollectionType: string
{
    case Manual = 'manual';
    case Automated = 'automated';

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
            self::Manual => 'Manual',
            self::Automated => 'Automated',
        };
    }
}
