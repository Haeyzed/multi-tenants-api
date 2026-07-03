<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Customer;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Customer>
 */
class CustomersExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Customer>  $customers
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $customers, ?array $columns = null)
    {
        parent::__construct($customers, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'customer_group_id',
            'customer_group_name',
            'date_of_birth',
            'gender',
            'loyalty_points',
            'total_spent',
            'orders_count',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Customer): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Customer $customer) => (string) $customer->id,
            ],
            'first_name' => [
                'heading' => 'First Name',
                'map' => fn (Customer $customer) => $customer->first_name,
            ],
            'last_name' => [
                'heading' => 'Last Name',
                'map' => fn (Customer $customer) => $customer->last_name,
            ],
            'email' => [
                'heading' => 'Email',
                'map' => fn (Customer $customer) => $customer->email,
            ],
            'phone' => [
                'heading' => 'Phone',
                'map' => fn (Customer $customer) => $customer->phone,
            ],
            'customer_group_id' => [
                'heading' => 'Customer Group ID',
                'map' => fn (Customer $customer) => $customer->customer_group_id !== null ? (string) $customer->customer_group_id : null,
            ],
            'customer_group_name' => [
                'heading' => 'Customer Group',
                'map' => fn (Customer $customer) => $customer->group?->name,
            ],
            'date_of_birth' => [
                'heading' => 'Date of Birth',
                'map' => fn (Customer $customer) => $customer->date_of_birth?->toDateString(),
            ],
            'gender' => [
                'heading' => 'Gender',
                'map' => fn (Customer $customer) => $customer->gender,
            ],
            'loyalty_points' => [
                'heading' => 'Loyalty Points',
                'map' => fn (Customer $customer) => (string) $customer->loyalty_points,
            ],
            'total_spent' => [
                'heading' => 'Total Spent',
                'map' => fn (Customer $customer) => (string) $customer->total_spent,
            ],
            'orders_count' => [
                'heading' => 'Orders Count',
                'map' => fn (Customer $customer) => (string) $customer->orders_count,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (Customer $customer) => $customer->is_active ? 'Yes' : 'No',
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Customer $customer) => $customer->created_at?->toDateTimeString(),
            ],
        ];
    }
}
