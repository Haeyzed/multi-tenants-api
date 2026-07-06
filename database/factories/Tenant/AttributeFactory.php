<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\AttributeDisplayType;
use App\Enums\Tenant\AttributeType;
use App\Models\Tenant\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $slug = Str::slug($name);

        return [
            'name' => ucwords($name),
            'slug' => $slug,
            'code' => Str::upper(Str::slug($slug, '_')),
            'type' => fake()->randomElement(AttributeType::values()),
            'display_type' => fake()->randomElement(AttributeDisplayType::values()),
            'is_filterable' => fake()->boolean(30),
            'is_variant' => fake()->boolean(20),
            'is_required' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
