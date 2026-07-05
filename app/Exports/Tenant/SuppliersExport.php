<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Supplier;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Supplier>
 */
class SuppliersExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Supplier>  $suppliers
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $suppliers, ?array $columns = null)
    {
        parent::__construct($suppliers, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'slug',
            'description',
            'contact_name',
            'contact_email',
            'contact_phone',
            'website_url',
            'tax_id',
            'registration_number',
            'is_active',
            'products_count',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Supplier): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Supplier $supplier) => (string) $supplier->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Supplier $supplier) => $supplier->name,
            ],
            'code' => [
                'heading' => 'Code',
                'map' => fn (Supplier $supplier) => $supplier->code,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Supplier $supplier) => $supplier->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (Supplier $supplier) => $supplier->description,
            ],
            'contact_name' => [
                'heading' => 'Contact Name',
                'map' => fn (Supplier $supplier) => $supplier->contact_name,
            ],
            'contact_email' => [
                'heading' => 'Contact Email',
                'map' => fn (Supplier $supplier) => $supplier->contact_email,
            ],
            'contact_phone' => [
                'heading' => 'Contact Phone',
                'map' => fn (Supplier $supplier) => $supplier->contact_phone,
            ],
            'website_url' => [
                'heading' => 'Website URL',
                'map' => fn (Supplier $supplier) => $supplier->website_url,
            ],
            'tax_id' => [
                'heading' => 'Tax ID',
                'map' => fn (Supplier $supplier) => $supplier->tax_id,
            ],
            'registration_number' => [
                'heading' => 'Registration Number',
                'map' => fn (Supplier $supplier) => $supplier->registration_number,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (Supplier $supplier) => $supplier->is_active ? 'Yes' : 'No',
            ],
            'products_count' => [
                'heading' => 'Products Count',
                'map' => fn (Supplier $supplier) => (string) $supplier->products_count,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Supplier $supplier) => $supplier->created_at?->toDateTimeString(),
            ],
        ];
    }
}
