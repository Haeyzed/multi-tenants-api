<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Exports\Central\Concerns\BaseCentralExport;
use App\Models\Central\Plan;
use Illuminate\Support\Collection;

/**
 * @extends BaseCentralExport<Plan>
 */
class PlansExport extends BaseCentralExport
{
    /**
     * @param  Collection<int, Plan>  $plans
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $plans, ?array $columns = null)
    {
        parent::__construct($plans, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'price',
            'currency',
            'interval',
            'is_active',
            'is_featured',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Plan): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Plan $plan) => (string) $plan->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Plan $plan) => $plan->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Plan $plan) => $plan->slug,
            ],
            'price' => [
                'heading' => 'Price',
                'map' => fn (Plan $plan) => (string) $plan->price,
            ],
            'currency' => [
                'heading' => 'Currency',
                'map' => fn (Plan $plan) => $plan->currency,
            ],
            'interval' => [
                'heading' => 'Interval',
                'map' => fn (Plan $plan) => $plan->interval,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (Plan $plan) => $plan->is_active ? 'Yes' : 'No',
            ],
            'is_featured' => [
                'heading' => 'Featured',
                'map' => fn (Plan $plan) => $plan->is_featured ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Plan $plan) => (string) $plan->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Plan $plan) => $plan->created_at?->toDateTimeString(),
            ],
        ];
    }
}
