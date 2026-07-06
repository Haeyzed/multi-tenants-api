<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum ProductVisibility: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';
    case Catalog = 'catalog';
    case Search = 'search';

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
            self::Visible => 'Visible',
            self::Hidden => 'Hidden',
            self::Catalog => 'Catalog Only',
            self::Search => 'Search Only',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this !== self::Hidden;
    }
}
