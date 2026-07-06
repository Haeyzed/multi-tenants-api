<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum AttributeDisplayType: string
{
    case Dropdown = 'dropdown';
    case Swatch = 'swatch';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case TextInput = 'text_input';

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
            self::Dropdown => 'Dropdown',
            self::Swatch => 'Swatch',
            self::Radio => 'Radio',
            self::Checkbox => 'Checkbox',
            self::TextInput => 'Text Input',
        };
    }
}
