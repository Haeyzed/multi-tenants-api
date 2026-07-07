<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\ProductCondition;
use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use App\Models\Tenant\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'subtitle' => fake()->optional()->sentence(6),
            'description' => fake()->paragraph(),
            'summary' => fake()->optional()->sentence(12),
            'type' => ProductType::Simple,
            'condition' => ProductCondition::New,
            'status' => ProductStatus::Draft,
            'visibility' => ProductVisibility::Visible,
            'is_featured' => false,
            'is_returnable' => true,
            'return_period_days' => 30,
            'warranty_period_months' => fake()->optional()->numberBetween(6, 36),
            'min_order_quantity' => 1,
            'max_order_quantity' => fake()->optional()->numberBetween(5, 100),
            'track_inventory' => true,
            'allow_backorders' => false,
            'requires_shipping' => true,
            'is_taxable' => true,
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(8),
            'meta_keywords' => fake()->optional()->words(5, true),
            'search_keywords' => fake()->optional()->words(4, true),
        ];
    }

    /**
     * Indicate that the product is active and published.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => ProductStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is a variable product.
     */
    public function variable(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => ProductType::Variable,
        ]);
    }
}
