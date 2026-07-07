<?php

declare(strict_types=1);

namespace Database\Factories\Central;

use App\Enums\Central\TenantStatus;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => TenantStatus::Pending,
            'plan_id' => fn() => Plan::query()->where('slug', 'starter')->value('id'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(): array => ['status' => TenantStatus::Active]);
    }

    public function suspended(): static
    {
        return $this->state(fn(): array => [
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function withPlan(string $slug): static
    {
        return $this->state(fn(): array => [
            'plan_id' => Plan::query()->where('slug', $slug)->value('id'),
        ]);
    }
}
