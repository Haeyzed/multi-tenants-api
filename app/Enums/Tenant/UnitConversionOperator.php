<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum UnitConversionOperator: string
{
    case Multiply = 'multiply';
    case Divide = 'divide';

    public function label(): string
    {
        return match ($this) {
            self::Multiply => 'Multiply (×)',
            self::Divide => 'Divide (÷)',
        };
    }
}
