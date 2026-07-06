<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum RelatedProductType: string
{
    case Related = 'related';
    case Accessory = 'accessory';
    case Replacement = 'replacement';
    case Upsell = 'upsell';
    case CrossSell = 'cross_sell';
    case FrequentlyBoughtTogether = 'frequently_bought_together';

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
            self::Related => 'Related',
            self::Accessory => 'Accessory',
            self::Replacement => 'Replacement',
            self::Upsell => 'Upsell',
            self::CrossSell => 'Cross-sell',
            self::FrequentlyBoughtTogether => 'Frequently Bought Together',
        };
    }
}
