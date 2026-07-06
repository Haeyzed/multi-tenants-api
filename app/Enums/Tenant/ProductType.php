<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * Catalog product type — defines behavior, not sellable SKU.
 */
enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';
    case Bundle = 'bundle';
    case Digital = 'digital';
    case Service = 'service';
    case Subscription = 'subscription';
    case GiftCard = 'gift_card';
    case Configurable = 'configurable';

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
            self::Simple => 'Simple',
            self::Variable => 'Variable',
            self::Bundle => 'Bundle',
            self::Digital => 'Digital',
            self::Service => 'Service',
            self::Subscription => 'Subscription',
            self::GiftCard => 'Gift Card',
            self::Configurable => 'Configurable',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Simple => 'Single SKU product with one default variant.',
            self::Variable => 'Product with multiple variants generated from options.',
            self::Bundle => 'Grouped sellable bundle of other products.',
            self::Digital => 'Downloadable or license-based product.',
            self::Service => 'Bookable service with scheduling.',
            self::Subscription => 'Recurring billing product.',
            self::GiftCard => 'Stored-value gift card product.',
            self::Configurable => 'Parent product with child simple variants.',
        };
    }

    public function requiresVariants(): bool
    {
        return match ($this) {
            self::Variable, self::Configurable => true,
            default => false,
        };
    }

    public function requiresShipping(): bool
    {
        return match ($this) {
            self::Simple, self::Variable, self::Bundle, self::Configurable => true,
            self::Digital, self::Service, self::Subscription, self::GiftCard => false,
        };
    }

    public function tracksInventory(): bool
    {
        return match ($this) {
            self::Digital, self::Service, self::GiftCard => false,
            default => true,
        };
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function toArray(): array
    {
        return array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ],
            self::cases()
        );
    }
}
