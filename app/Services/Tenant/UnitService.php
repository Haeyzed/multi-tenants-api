<?php

declare(strict_types=1);

namespace App\Services\Tenant;

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
     * @param int $perPage
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
     *
     * @param int $id
     * @return Unit
     */
    public function find(int $id): Unit
    {
        return Unit::query()->findOrFail($id);
    }

    /**
     * Find a unit by code.
     *
     * @param string $code
     * @return Unit
     */
    public function findByCode(string $code): Unit
    {
        return Unit::query()->where('code', $code)->firstOrFail();
    }

    /**
     * Create a new unit.
     *
     * @param array<string, mixed> $data
     * @return Unit
     * @throws Throwable
     */
    public function create(array $data): Unit
    {
        return DB::transaction(function () use ($data): Unit {
            if (! empty($data['is_base'])) {
                $this->clearBaseUnitForType((string) $data['type']);
            }

            return Unit::query()->create($data);
        });
    }

    /**
     * Update a unit.
     *
     * @param Unit $unit
     * @param array<string, mixed> $data
     * @return Unit
     * @throws Throwable
     */
    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data): Unit {
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
     *
     * @param Unit $unit
     * @return void
     */
    public function delete(Unit $unit): void
    {
        $unit->delete();
    }

    /**
     * Delete multiple units by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Unit::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
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
     * @param string|null $type
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
     * @param string $type
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
     *
     * @param string $type
     * @return Unit|null
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
     *
     * @param Unit $unit
     * @return Unit
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
     *
     * @param float $value
     * @param string $fromCode
     * @param string $toCode
     * @return float
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
     * @return void
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
     * Clear the base flag for all units of a type except the given ID.
     *
     * @param string $type
     * @param int|null $exceptId
     * @return void
     */
    private function clearBaseUnitForType(string $type, ?int $exceptId = null): void
    {
        Unit::query()
            ->where('type', $type)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_base' => false]);
    }
}
