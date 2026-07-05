<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxRule;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages tax rates within a tenant store.
 */
class TaxRateService
{
    /**
     * @var list<string>
     */
    private const LIST_RELATIONS = ['taxClass', 'taxZone', 'rules'];

    /**
     * Paginate tax rates.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TaxRate>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return TaxRate::query()
            ->with(self::LIST_RELATIONS)
            ->filter($filters)
            ->orderBy('priority')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a tax rate by ID.
     */
    public function find(int $id): TaxRate
    {
        return TaxRate::query()
            ->with(self::LIST_RELATIONS)
            ->findOrFail($id);
    }

    /**
     * Create a new tax rate.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TaxRate
    {
        return DB::transaction(function () use ($data): TaxRate {
            $taxRate = TaxRate::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_rate');

            return $taxRate->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Update a tax rate.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TaxRate $taxRate, array $data): TaxRate
    {
        return DB::transaction(function () use ($taxRate, $data): TaxRate {
            $taxRate->update($data);
            TaxConfigurationUpdated::dispatch('tax_rate');

            return $taxRate->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Soft delete a tax rate.
     */
    public function delete(TaxRate $taxRate): void
    {
        DB::transaction(function () use ($taxRate): void {
            $taxRate->delete();
            TaxConfigurationUpdated::dispatch('tax_rate');
        });
    }

    /**
     * Soft delete multiple tax rates by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $taxRates = TaxRate::query()->whereIn('id', $ids)->get();
            $deleted = 0;

            foreach ($taxRates as $taxRate) {
                $taxRate->delete();
                $deleted++;
            }

            if ($deleted > 0) {
                TaxConfigurationUpdated::dispatch('tax_rate');
            }

            return $deleted;
        });
    }

    /**
     * Permanently delete a tax rate.
     */
    public function forceDelete(TaxRate $taxRate): void
    {
        if ($taxRate->rules()->exists()) {
            throw new DomainException('Cannot permanently delete tax rate with associated rules.');
        }

        DB::transaction(function () use ($taxRate): void {
            $taxRate->forceDelete();
            TaxConfigurationUpdated::dispatch('tax_rate');
        });
    }

    /**
     * Restore a soft-deleted tax rate.
     */
    public function restore(TaxRate $taxRate): TaxRate
    {
        $taxRate->restore();
        TaxConfigurationUpdated::dispatch('tax_rate');

        return $taxRate->fresh(self::LIST_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted tax rates by ID.
     *
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        $count = TaxRate::query()->onlyTrashed()->whereIn('id', $ids)->restore();

        if ($count > 0) {
            TaxConfigurationUpdated::dispatch('tax_rate');
        }

        return $count;
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @return EloquentCollection<int, TaxRate>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): EloquentCollection {
        $query = TaxRate::query()
            ->with(['taxClass', 'taxZone'])
            ->orderBy('priority')
            ->latest();

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get all currently active tax rates.
     *
     * @return EloquentCollection<int, TaxRate>
     */
    public function getActive(): EloquentCollection
    {
        return TaxRate::query()
            ->active()
            ->with(['taxClass', 'taxZone'])
            ->orderBy('priority')
            ->get();
    }

    /**
     * Get active rates for a class and zone combination.
     *
     * @return EloquentCollection<int, TaxRate>
     */
    public function getByClassAndZone(int $taxClassId, int $taxZoneId): EloquentCollection
    {
        return TaxRate::query()
            ->where('tax_class_id', $taxClassId)
            ->where('tax_zone_id', $taxZoneId)
            ->active()
            ->orderBy('priority')
            ->get();
    }

    /**
     * Calculate tax for an amount using class and zone.
     */
    public function calculateTax(float $amount, int $taxClassId, int $taxZoneId): float
    {
        $rates = $this->getByClassAndZone($taxClassId, $taxZoneId);
        $totalTax = 0.0;
        $runningSubtotal = $amount;

        foreach ($rates as $rate) {
            $tax = $rate->is_compound
                ? $runningSubtotal * ((float) $rate->rate / 100)
                : $amount * ((float) $rate->rate / 100);

            $totalTax += $tax;

            if ($rate->is_compound) {
                $runningSubtotal += $tax;
            }
        }

        return round($totalTax, 2);
    }

    /**
     * Calculate tax for a product in a zone, applying product-specific rules when present.
     */
    public function calculateTaxForProduct(Product $product, float $amount, int $taxZoneId): float
    {
        if (! $product->tax_class_id) {
            return 0.0;
        }

        $rates = $product->taxClass
            ->rates()
            ->where('tax_zone_id', $taxZoneId)
            ->active()
            ->with(['rules' => function ($q) use ($product): void {
                $q->where('applicable_type', Product::class)
                    ->where('applicable_id', $product->id)
                    ->where('is_active', true);
            }])
            ->orderBy('priority')
            ->get();

        if ($rates->isEmpty()) {
            return $this->calculateTax($amount, $product->tax_class_id, $taxZoneId);
        }

        $totalTax = 0.0;
        $runningSubtotal = $amount;

        foreach ($rates as $rate) {
            $applicableRate = (float) $rate->rate;

            foreach ($rate->rules as $rule) {
                $applicableRate = $this->applyRuleAdjustment($applicableRate, $rule);
            }

            $tax = $rate->is_compound
                ? $runningSubtotal * ($applicableRate / 100)
                : $amount * ($applicableRate / 100);

            $totalTax += $tax;

            if ($rate->is_compound) {
                $runningSubtotal += $tax;
            }
        }

        return round($totalTax, 2);
    }

    /**
     * Toggle the active status of a tax rate.
     */
    public function toggleActive(TaxRate $taxRate): TaxRate
    {
        $taxRate->update(['is_active' => ! $taxRate->is_active]);
        TaxConfigurationUpdated::dispatch('tax_rate');

        return $taxRate->fresh(self::LIST_RELATIONS);
    }

    /**
     * Get rules for a tax rate.
     *
     * @return EloquentCollection<int, TaxRule>
     */
    public function getRules(TaxRate $taxRate): EloquentCollection
    {
        return $this->find($taxRate->id)->rules;
    }

    /**
     * Return active tax rates formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return TaxRate::query()
            ->where('is_active', true)
            ->with(['taxClass', 'taxZone'])
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(fn (TaxRate $taxRate): array => [
                'label' => sprintf(
                    '%s (%s / %s)',
                    $taxRate->name,
                    $taxRate->taxClass?->name ?? 'Class',
                    $taxRate->taxZone?->name ?? 'Zone',
                ),
                'value' => $taxRate->id,
            ]);
    }

    /**
     * Return aggregate counts for the admin dashboard.
     *
     * @return array{total: int, active: int, inactive: int, compound: int}
     */
    public function statistics(): array
    {
        return [
            'total' => TaxRate::query()->count(),
            'active' => TaxRate::query()->where('is_active', true)->count(),
            'inactive' => TaxRate::query()->where('is_active', false)->count(),
            'compound' => TaxRate::query()->where('is_compound', true)->count(),
        ];
    }

    private function applyRuleAdjustment(float $rate, TaxRule $rule): float
    {
        return match ($rule->rule_type) {
            'override' => (float) ($rule->adjustment_rate ?? 0),
            'exempt' => 0.0,
            'reduce' => $rate - (float) ($rule->adjustment_rate ?? 0),
            'increase' => $rate + (float) ($rule->adjustment_rate ?? 0),
            default => $rate,
        };
    }
}
