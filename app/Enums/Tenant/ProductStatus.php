<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function isPublished(): bool
    {
        return $this === self::Active;
    }
}
