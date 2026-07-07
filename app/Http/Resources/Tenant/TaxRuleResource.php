<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\CustomerGroup;
use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaxRule
 */
class TaxRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_rate_id' => $this->tax_rate_id,
            'applicable_type' => $this->resolveApplicableKey($this->applicable_type),
            'applicable_id' => $this->applicable_id,
            'rule_type' => $this->rule_type,
            'adjustment_rate' => $this->adjustment_rate,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'is_active' => $this->is_active,
            'tax_rate' => $this->whenLoaded('taxRate', fn() => new TaxRateResource($this->taxRate)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolveApplicableKey(string $type): string
    {
        return match ($type) {
            Product::class => 'product',
            CustomerGroup::class => 'customer_group',
            default => $type,
        };
    }
}
