<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\CustomerGroup;
use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRule;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<TaxRule>
 */
class TaxRulesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, TaxRule>  $taxRules
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $taxRules, ?array $columns = null)
    {
        parent::__construct($taxRules, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'tax_rate',
            'applicable_type',
            'applicable_id',
            'rule_type',
            'adjustment_rate',
            'effective_from',
            'effective_to',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(TaxRule): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (TaxRule $taxRule) => (string) $taxRule->id,
            ],
            'tax_rate' => [
                'heading' => 'Tax Rate',
                'map' => fn (TaxRule $taxRule) => $taxRule->taxRate?->name,
            ],
            'applicable_type' => [
                'heading' => 'Applicable Type',
                'map' => fn (TaxRule $taxRule) => match ($taxRule->applicable_type) {
                    Product::class => 'Product',
                    CustomerGroup::class => 'Customer Group',
                    default => $taxRule->applicable_type,
                },
            ],
            'applicable_id' => [
                'heading' => 'Applicable ID',
                'map' => fn (TaxRule $taxRule) => (string) $taxRule->applicable_id,
            ],
            'rule_type' => [
                'heading' => 'Rule Type',
                'map' => fn (TaxRule $taxRule) => $taxRule->rule_type,
            ],
            'adjustment_rate' => [
                'heading' => 'Adjustment Rate',
                'map' => fn (TaxRule $taxRule) => $taxRule->adjustment_rate !== null
                    ? (string) $taxRule->adjustment_rate
                    : null,
            ],
            'effective_from' => [
                'heading' => 'Effective From',
                'map' => fn (TaxRule $taxRule) => $taxRule->effective_from?->toDateString(),
            ],
            'effective_to' => [
                'heading' => 'Effective To',
                'map' => fn (TaxRule $taxRule) => $taxRule->effective_to?->toDateString(),
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (TaxRule $taxRule) => $taxRule->is_active ? 'Yes' : 'No',
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (TaxRule $taxRule) => $taxRule->created_at?->toDateTimeString(),
            ],
        ];
    }
}
