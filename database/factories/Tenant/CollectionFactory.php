<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\CollectionType;
use App\Models\Tenant\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_visible' => true,
            'is_featured' => fake()->boolean(20),
            'type' => CollectionType::Manual->value,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
