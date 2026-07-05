<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\TaxRate;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<TaxRate>
 */
class TaxRatesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, TaxRate>  $taxRates
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $taxRates, ?array $columns = null)
    {
        parent::__construct($taxRates, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'name',
            'tax_class',
            'tax_zone',
            'rate',
            'priority',
            'is_compound',
            'applies_to_shipping',
            'effective_from',
            'effective_to',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(TaxRate): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (TaxRate $taxRate) => (string) $taxRate->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (TaxRate $taxRate) => $taxRate->name,
            ],
            'tax_class' => [
                'heading' => 'Tax Class',
                'map' => fn (TaxRate $taxRate) => $taxRate->taxClass?->name,
            ],
            'tax_zone' => [
                'heading' => 'Tax Zone',
                'map' => fn (TaxRate $taxRate) => $taxRate->taxZone?->name,
            ],
            'rate' => [
                'heading' => 'Rate (%)',
                'map' => fn (TaxRate $taxRate) => (string) $taxRate->rate,
            ],
            'priority' => [
                'heading' => 'Priority',
                'map' => fn (TaxRate $taxRate) => (string) $taxRate->priority,
            ],
            'is_compound' => [
                'heading' => 'Compound',
                'map' => fn (TaxRate $taxRate) => $taxRate->is_compound ? 'Yes' : 'No',
            ],
            'applies_to_shipping' => [
                'heading' => 'Applies to Shipping',
                'map' => fn (TaxRate $taxRate) => $taxRate->applies_to_shipping ? 'Yes' : 'No',
            ],
            'effective_from' => [
                'heading' => 'Effective From',
                'map' => fn (TaxRate $taxRate) => $taxRate->effective_from?->toDateString(),
            ],
            'effective_to' => [
                'heading' => 'Effective To',
                'map' => fn (TaxRate $taxRate) => $taxRate->effective_to?->toDateString(),
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (TaxRate $taxRate) => $taxRate->is_active ? 'Yes' : 'No',
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (TaxRate $taxRate) => $taxRate->created_at?->toDateTimeString(),
            ],
        ];
    }
}
