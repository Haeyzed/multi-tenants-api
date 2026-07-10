<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Enums\Tenant\UnitConversionOperator;
use App\Models\Tenant\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Unit
 */
class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $operator = $this->conversion_operator;
        $value = $this->conversion_value !== null ? (float) $this->conversion_value : null;
        $factor = (float) $this->conversion_factor;

        $example = $this->is_base
            ? 'Base unit — inventory is stored in this unit.'
            : match ($operator) {
                UnitConversionOperator::Multiply => "1 {$this->name} = {$factor} base units",
                UnitConversionOperator::Divide => "{$factor} base units = 1 {$this->name}",
                default => "1 {$this->name} = {$factor} base units",
            };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'type' => $this->type,
            'conversion_factor' => $this->conversion_factor,
            'conversion_operator' => $operator?->value,
            'conversion_operator_label' => $operator?->label(),
            'conversion_value' => $this->conversion_value,
            'conversion_example' => $example,
            'is_base' => $this->is_base,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
