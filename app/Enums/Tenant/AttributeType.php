<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum AttributeType: string
{
    case Select = 'select';
    case Text = 'text';
    case Textarea = 'textarea';
    case Boolean = 'boolean';
    case Number = 'number';
    case Date = 'date';
    case Color = 'color';

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
            self::Select => 'Select',
            self::Text => 'Text',
            self::Textarea => 'Textarea',
            self::Boolean => 'Boolean',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Color => 'Color',
        };
    }
}
