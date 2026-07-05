<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\UnitType;
use App\Models\Tenant\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'code' => Str::slug($name),
            'symbol' => Str::upper(Str::substr($name, 0, 3)),
            'type' => fake()->randomElement(UnitType::values()),
            'conversion_factor' => 1,
            'is_base' => false,
            'sort_order' => 0,
        ];
    }
}
