<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\ProductVisibility;
use App\Enums\Tenant\VariantStatus;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(4, 10, 500);

        return [
            'product_id' => Product::factory(),
            'title' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'barcode' => fake()->optional()->ean13(),
            'gtin' => fake()->optional()->numerify('##############'),
            'mpn' => fake()->optional()->bothify('MPN-???-####'),
            'price' => $price,
            'compare_at_price' => fake()->optional(0.3)->randomFloat(4, $price + 1, $price + 100),
            'cost_price' => fake()->optional()->randomFloat(4, 5, $price - 1),
            'weight' => fake()->optional()->randomFloat(4, 0.1, 25),
            'length' => fake()->optional()->randomFloat(4, 1, 100),
            'width' => fake()->optional()->randomFloat(4, 1, 100),
            'height' => fake()->optional()->randomFloat(4, 1, 100),
            'status' => VariantStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'is_default' => false,
            'position' => fake()->numberBetween(0, 10),
            'hs_code' => fake()->optional()->numerify('####.##.##'),
            'country_of_origin' => fake()->optional()->countryCode(),
        ];
    }

    /**
     * Indicate that the variant is the default for its product.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'position' => 0,
        ]);
    }

    /**
     * Indicate that the variant is active and sellable.
     */
    public function sellable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VariantStatus::Active,
            'visibility' => ProductVisibility::Visible,
        ]);
    }
}
