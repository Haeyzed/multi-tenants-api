<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Exports\Central\Concerns\BaseCentralExport;
use App\Models\Central\Tenant;
use Illuminate\Support\Collection;

/**
 * @extends BaseCentralExport<Tenant>
 */
class TenantsExport extends BaseCentralExport
{
    /**
     * @param  Collection<int, Tenant>  $tenants
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $tenants, ?array $columns = null)
    {
        parent::__construct($tenants, $columns);
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
            'email',
            'phone',
            'plan',
            'status',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Tenant): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Tenant $tenant) => $tenant->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Tenant $tenant) => $tenant->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Tenant $tenant) => $tenant->slug,
            ],
            'email' => [
                'heading' => 'Email',
                'map' => fn (Tenant $tenant) => $tenant->email,
            ],
            'phone' => [
                'heading' => 'Phone',
                'map' => fn (Tenant $tenant) => $tenant->phone,
            ],
            'plan' => [
                'heading' => 'Plan',
                'map' => fn (Tenant $tenant) => $tenant->plan?->slug,
            ],
            'status' => [
                'heading' => 'Status',
                'map' => fn (Tenant $tenant) => $tenant->status->value ?? (string) $tenant->status,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Tenant $tenant) => $tenant->created_at?->toDateTimeString(),
            ],
        ];
    }
}
