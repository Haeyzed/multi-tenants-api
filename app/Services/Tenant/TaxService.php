<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\TaxType;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxRegion;
use Illuminate\Support\Collection;

/**
 * Calculates tax for amounts by region.
 */
class TaxService
{
    /**
     * Calculate tax for an amount by region.
     *
     * @param  array{country_code: string, state_code?: string|null, postal_code?: string|null}  $region
     * @return array{subtotal: float, tax_total: float, total: float, breakdown: list<array{rate_id: int, name: string, type: string, rate: string, amount: float}>}
     */
    public function calculate(float $amount, array $region, ?int $taxClassId = null): array
    {
        $taxRegion = $this->resolveRegion($region);

        if ($taxRegion === null) {
            return [
                'subtotal' => $amount,
                'tax_total' => 0.0,
                'total' => $amount,
                'breakdown' => [],
            ];
        }

        $rates = $this->resolveRates($taxRegion, $taxClassId);

        $taxableAmount = $amount;
        $taxTotal = 0.0;
        $breakdown = [];

        foreach ($rates as $rate) {
            $taxAmount = match ($rate->type) {
                TaxType::Percentage => round($taxableAmount * ((float) $rate->rate / 100), 2),
                TaxType::Fixed => round((float) $rate->rate, 2),
            };

            $taxTotal += $taxAmount;

            if ($rate->is_compound) {
                $taxableAmount += $taxAmount;
            }

            $breakdown[] = [
                'rate_id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type->value,
                'rate' => (string) $rate->rate,
                'amount' => $taxAmount,
            ];
        }

        return [
            'subtotal' => $amount,
            'tax_total' => round($taxTotal, 2),
            'total' => round($amount + $taxTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @param  array{country_code: string, state_code?: string|null, postal_code?: string|null}  $region
     */
    private function resolveRegion(array $region): ?TaxRegion
    {
        $query = TaxRegion::query()
            ->where('is_active', true)
            ->where('country_code', $region['country_code']);

        if (! empty($region['state_code'])) {
            $query->where(function ($builder) use ($region): void {
                $builder->where('state_code', $region['state_code'])
                    ->orWhereNull('state_code');
            });
        }

        $regions = $query->get();

        if ($regions->isEmpty()) {
            return null;
        }

        if (! empty($region['postal_code'])) {
            $matched = $regions->first(function (TaxRegion $taxRegion) use ($region): bool {
                if ($taxRegion->postal_code_pattern === null) {
                    return true;
                }

                return (bool) preg_match('/'.$taxRegion->postal_code_pattern.'/', $region['postal_code']);
            });

            return $matched ?? $regions->first();
        }

        return $regions->first();
    }

    /**
     * @return Collection<int, TaxRate>
     */
    private function resolveRates(TaxRegion $taxRegion, ?int $taxClassId): Collection
    {
        $query = TaxRate::query()
            ->where('is_active', true)
            ->whereHas('rules', function ($builder) use ($taxRegion): void {
                $builder->where('is_active', true)
                    ->where(function ($ruleQuery) use ($taxRegion): void {
                        $ruleQuery->where('tax_region_id', $taxRegion->id)
                            ->orWhereNull('tax_region_id');
                    });
            })
            ->orderBy('priority');

        if ($taxClassId !== null) {
            $query->where('tax_class_id', $taxClassId);
        }

        return $query->get();
    }
}
