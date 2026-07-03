<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\CustomerGroup;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<CustomerGroup>
 */
class CustomerGroupsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, CustomerGroup>  $groups
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $groups, ?array $columns = null)
    {
        parent::__construct($groups, $columns);
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
            'description',
            'discount_percent',
            'is_active',
            'customers_count',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(CustomerGroup): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (CustomerGroup $group) => (string) $group->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (CustomerGroup $group) => $group->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (CustomerGroup $group) => $group->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (CustomerGroup $group) => $group->description,
            ],
            'discount_percent' => [
                'heading' => 'Discount %',
                'map' => fn (CustomerGroup $group) => $group->discount_percent !== null ? (string) $group->discount_percent : null,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (CustomerGroup $group) => $group->is_active ? 'Yes' : 'No',
            ],
            'customers_count' => [
                'heading' => 'Customers Count',
                'map' => fn (CustomerGroup $group) => (string) ($group->customers_count ?? 0),
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (CustomerGroup $group) => $group->created_at?->toDateTimeString(),
            ],
        ];
    }
}
