<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TaxRate;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages tax classes within a tenant store.
 */
class TaxClassService
{
    /**
     * @var list<string>
     */
    private const LIST_RELATIONS = ['rates'];

    /**
     * Paginate tax classes.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TaxClass>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return TaxClass::query()
            ->withCount('rates')
            ->withCount('products')
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a tax class by ID.
     *
     * @param int $id
     * @return TaxClass
     */
    public function find(int $id): TaxClass
    {
        return TaxClass::query()
            ->with(self::LIST_RELATIONS)
            ->withCount(['rates', 'products'])
            ->findOrFail($id);
    }

    /**
     * Find a tax class by code.
     *
     * @param string $code
     * @return TaxClass
     */
    public function findByCode(string $code): TaxClass
    {
        return TaxClass::query()
            ->with(self::LIST_RELATIONS)
            ->withCount(['rates', 'products'])
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * Create a new tax class.
     *
     * @param  array<string, mixed>  $data
     * @return TaxClass
     */
    public function create(array $data): TaxClass
    {
        return DB::transaction(function () use ($data): TaxClass {
            if (! empty($data['is_default'])) {
                TaxClass::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $taxClass = TaxClass::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_class');

            return $taxClass->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Update a tax class.
     *
     * @param TaxClass $taxClass
     * @param  array<string, mixed>  $data
     * @return TaxClass
     */
    public function update(TaxClass $taxClass, array $data): TaxClass
    {
        return DB::transaction(function () use ($taxClass, $data): TaxClass {
            if (! empty($data['is_default'])) {
                TaxClass::query()
                    ->where('id', '!=', $taxClass->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $taxClass->update($data);
            TaxConfigurationUpdated::dispatch('tax_class');

            return $taxClass->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Soft delete a tax class.
     *
     * @param TaxClass $taxClass
     * @return void
     */
    public function delete(TaxClass $taxClass): void
    {
        DB::transaction(function () use ($taxClass): void {
            if ($taxClass->is_default) {
                $taxClass->update(['is_default' => false]);
            }

            $taxClass->delete();
            TaxConfigurationUpdated::dispatch('tax_class');
        });
    }

    /**
     * Soft delete multiple tax classes by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $taxClasses = TaxClass::query()->whereIn('id', $ids)->get();
            $deleted = 0;

            foreach ($taxClasses as $taxClass) {
                if ($taxClass->is_default) {
                    $taxClass->update(['is_default' => false]);
                }

                $taxClass->delete();
                $deleted++;
            }

            if ($deleted > 0) {
                TaxConfigurationUpdated::dispatch('tax_class');
            }

            return $deleted;
        });
    }

    /**
     * Permanently delete a tax class.
     *
     * @param TaxClass $taxClass
     * @return void
     */
    public function forceDelete(TaxClass $taxClass): void
    {
        if ($taxClass->products()->exists()) {
            throw new DomainException('Cannot permanently delete tax class with associated products.');
        }

        DB::transaction(function () use ($taxClass): void {
            $taxClass->forceDelete();
            TaxConfigurationUpdated::dispatch('tax_class');
        });
    }

    /**
     * Restore a soft-deleted tax class.
     *
     * @param TaxClass $taxClass
     * @return TaxClass
     */
    public function restore(TaxClass $taxClass): TaxClass
    {
        $taxClass->restore();
        TaxConfigurationUpdated::dispatch('tax_class');

        return $taxClass->fresh(self::LIST_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted tax classes by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        $count = TaxClass::query()->onlyTrashed()->whereIn('id', $ids)->restore();

        if ($count > 0) {
            TaxConfigurationUpdated::dispatch('tax_class');
        }

        return $count;
    }

    /**
     * Reorder tax classes by ID.
     *
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                TaxClass::query()->where('id', $id)->update(['sort_order' => $index + 1]);
            }

            TaxConfigurationUpdated::dispatch('tax_class');
        });
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return EloquentCollection<int, TaxClass>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): EloquentCollection {
        $query = TaxClass::query()->orderBy('sort_order')->latest();

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
     * Get rates for a tax class.
     *
     * @param TaxClass $taxClass
     * @return EloquentCollection<int, TaxRate>
     */
    public function getRates(TaxClass $taxClass): EloquentCollection
    {
        return $this->find($taxClass->id)->rates;
    }

    /**
     * Set a tax class as the default.
     *
     * @param TaxClass $taxClass
     * @return TaxClass
     */
    public function setDefault(TaxClass $taxClass): TaxClass
    {
        return DB::transaction(function () use ($taxClass): TaxClass {
            TaxClass::query()->where('is_default', true)->update(['is_default' => false]);
            $taxClass->update(['is_default' => true, 'is_active' => true]);
            TaxConfigurationUpdated::dispatch('tax_class');

            return $taxClass->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Get the default tax class.
     *
     * @return TaxClass|null
     */
    public function getDefault(): ?TaxClass
    {
        return TaxClass::query()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Toggle the active status of a tax class.
     *
     * @param TaxClass $taxClass
     * @return TaxClass
     */
    public function toggleActive(TaxClass $taxClass): TaxClass
    {
        $taxClass->update(['is_active' => ! $taxClass->is_active]);
        TaxConfigurationUpdated::dispatch('tax_class');

        return $taxClass->fresh(self::LIST_RELATIONS);
    }

    /**
     * Return aggregate counts for the admin dashboard.
     *
     * @return array{total: int, active: int, inactive: int, default: int}
     */
    public function statistics(): array
    {
        return [
            'total' => TaxClass::query()->count(),
            'active' => TaxClass::query()->where('is_active', true)->count(),
            'inactive' => TaxClass::query()->where('is_active', false)->count(),
            'default' => TaxClass::query()->where('is_default', true)->count(),
        ];
    }

    /**
     * Return active tax classes formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return TaxClass::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (TaxClass $taxClass): array => [
                'label' => $taxClass->name,
                'value' => $taxClass->id,
            ]);
    }
}
