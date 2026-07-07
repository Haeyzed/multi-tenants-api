<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\ProductLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLabel>
 */
class ProductLabelFactory extends Factory
{
    protected $model = ProductLabel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }
}
