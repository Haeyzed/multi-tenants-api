<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\UnitConversionOperator;
use App\Enums\Tenant\UnitType;
use App\Models\Tenant\Unit;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages measurement units within a tenant store.
 */
class UnitService
{
    /**
     * Paginate units.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Unit::query()
            ->filter($filters)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find a unit by ID.
     */
    public function find(int $id): Unit
    {
        return Unit::query()->findOrFail($id);
    }

    /**
     * Find a unit by code.
     */
    public function findByCode(string $code): Unit
    {
        return Unit::query()->where('code', $code)->firstOrFail();
    }

    /**
     * Create a new unit.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data): Unit
    {
        return DB::transaction(function () use ($data): Unit {
            $data = $this->normalizeConversionFields($data);

            if (! empty($data['is_base'])) {
                $this->clearBaseUnitForType((string) $data['type']);
            }

            return Unit::query()->create($data);
        });
    }

    /**
     * Update a unit.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data): Unit {
            $data = $this->normalizeConversionFields($data, $unit);
            $type = (string) ($data['type'] ?? $unit->type);

            if (! empty($data['is_base'])) {
                $this->clearBaseUnitForType($type, $unit->id);
            }

            $unit->update($data);

            return $unit->fresh();
        });
    }

    /**
     * Delete a unit.
     */
    public function delete(Unit $unit): void
    {
        $unit->delete();
    }

    /**
     * Delete multiple units by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Unit::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @return Collection<int, Unit>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Unit::query()->orderBy('sort_order')->orderBy('name');

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
     * Return aggregate counts for the admin dashboard.
     *
     * @return array{total: int, base: int, types: array<string, int>}
     */
    public function statistics(): array
    {
        $byType = Unit::query()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->all();

        return [
            'total' => Unit::query()->count(),
            'base' => Unit::query()->where('is_base', true)->count(),
            'types' => $byType,
        ];
    }

    /**
     * Return units formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int, type: string, symbol: string}>
     */
    public function getOptions(?string $type = null): Collection
    {
        return Unit::query()
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'symbol'])
            ->map(fn (Unit $unit) => [
                'label' => "{$unit->name} ({$unit->symbol})",
                'value' => $unit->id,
                'type' => $unit->type,
                'symbol' => $unit->symbol,
            ]);
    }

    /**
     * Get units by type.
     *
     * @return EloquentCollection<int, Unit>
     */
    public function getByType(string $type): EloquentCollection
    {
        return Unit::query()
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the base unit for a type.
     */
    public function getBaseUnit(string $type): ?Unit
    {
        return Unit::query()
            ->where('type', $type)
            ->where('is_base', true)
            ->first();
    }

    /**
     * Set a unit as the base unit for its type.
     */
    public function setBase(Unit $unit): Unit
    {
        return DB::transaction(function () use ($unit): Unit {
            $this->clearBaseUnitForType($unit->type, $unit->id);
            $unit->update(['is_base' => true]);

            return $unit->fresh();
        });
    }

    /**
     * Convert a value between unit codes.
     */
    public function convert(float $value, string $fromCode, string $toCode): float
    {
        $from = $this->findByCode($fromCode);
        $to = $this->findByCode($toCode);

        if ($from->type !== $to->type) {
            throw new DomainException('Cannot convert between different unit types.');
        }

        $baseValue = $value * (float) $from->conversion_factor;

        return $baseValue / (float) $to->conversion_factor;
    }

    /**
     * Persist sort order values from an ordered ID list.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Unit::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Return available unit type options.
     *
     * @return Collection<int, array{label: string, value: string}>
     */
    public function getTypeOptions(): Collection
    {
        return collect(UnitType::cases())->map(fn (UnitType $type) => [
            'label' => $type->label(),
            'value' => $type->value,
        ]);
    }

    /**
     * Derive canonical conversion_factor from operator + value (both map to the same factor).
     *
     * Multiply: 1 unit = N base units (e.g. 1 Carton = 24 Pieces).
     * Divide: N base units = 1 unit (e.g. 1000 Grams = 1 Kilogram).
     */
    public function deriveConversionFactor(
        ?UnitConversionOperator $operator,
        ?float $value,
        bool $isBase = false
    ): float {
        if ($isBase) {
            return 1.0;
        }

        if ($value === null || $value <= 0) {
            throw new DomainException('Conversion value must be greater than zero for non-base units.');
        }

        if ($operator === null) {
            throw new DomainException('Conversion operator is required for non-base units.');
        }

        return $value;
    }

    /**
     * Human-readable conversion example for API consumers.
     *
     * @return array{operator: string|null, value: float|null, factor: float, example: string|null}
     */
    public function conversionExample(Unit $unit): array
    {
        if ($unit->is_base) {
            return [
                'operator' => null,
                'value' => null,
                'factor' => 1.0,
                'example' => 'Base unit — inventory is stored in this unit.',
            ];
        }

        $operator = $unit->conversion_operator?->value;
        $value = $unit->conversion_value !== null ? (float) $unit->conversion_value : null;
        $factor = (float) $unit->conversion_factor;

        $example = match ($unit->conversion_operator) {
            UnitConversionOperator::Multiply => "1 {$unit->name} = {$factor} base units",
            UnitConversionOperator::Divide => "{$factor} base units = 1 {$unit->name}",
            default => "1 {$unit->name} = {$factor} base units",
        };

        return [
            'operator' => $operator,
            'value' => $value,
            'factor' => $factor,
            'example' => $example,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeConversionFields(array $data, ?Unit $existing = null): array
    {
        $isBase = ! empty($data['is_base']) || ($existing?->is_base ?? false);

        if ($isBase) {
            $data['conversion_factor'] = 1;
            $data['conversion_operator'] = null;
            $data['conversion_value'] = null;

            return $data;
        }

        $operator = $data['conversion_operator'] ?? $existing?->conversion_operator?->value;
        $value = array_key_exists('conversion_value', $data)
            ? $data['conversion_value']
            : $existing?->conversion_value;

        if ($operator !== null && $value !== null) {
            $enum = $operator instanceof UnitConversionOperator
                ? $operator
                : UnitConversionOperator::from((string) $operator);

            $data['conversion_operator'] = $enum->value;
            $data['conversion_value'] = $value;
            $data['conversion_factor'] = $this->deriveConversionFactor(
                $enum,
                (float) $value,
                false
            );

            return $data;
        }

        if (isset($data['conversion_factor'])) {
            $data['conversion_factor'] = (float) $data['conversion_factor'];
        }

        return $data;
    }

    /**
     * Clear the base flag for all units of a type except the given ID.
     */
    private function clearBaseUnitForType(string $type, ?int $exceptId = null): void
    {
        Unit::query()
            ->where('type', $type)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_base' => false]);
    }
}
