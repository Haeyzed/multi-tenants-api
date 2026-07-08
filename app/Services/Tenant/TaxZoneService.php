<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\TaxZone;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages tax zones within a tenant store.
 */
class TaxZoneService
{
    /**
     * @var list<string>
     */
    private const array LIST_RELATIONS = ['rates'];

    /**
     * Paginate tax zones.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TaxZone>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return TaxZone::query()
            ->withCount('rates')
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a tax zone by ID.
     *
     * @param int $id
     * @return TaxZone
     */
    public function find(int $id): TaxZone
    {
        return TaxZone::query()
            ->with(self::LIST_RELATIONS)
            ->withCount('rates')
            ->findOrFail($id);
    }

    /**
     * Create a new tax zone.
     *
     * @param array<string, mixed> $data
     * @return TaxZone
     * @throws Throwable
     */
    public function create(array $data): TaxZone
    {
        return DB::transaction(function () use ($data): TaxZone {
            if (! empty($data['is_default'])) {
                TaxZone::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $taxZone = TaxZone::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_zone');

            return $taxZone->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Update a tax zone.
     *
     * @param TaxZone $taxZone
     * @param array<string, mixed> $data
     * @return TaxZone
     * @throws Throwable
     */
    public function update(TaxZone $taxZone, array $data): TaxZone
    {
        return DB::transaction(function () use ($taxZone, $data): TaxZone {
            if (! empty($data['is_default'])) {
                TaxZone::query()
                    ->where('id', '!=', $taxZone->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $taxZone->update($data);
            TaxConfigurationUpdated::dispatch('tax_zone');

            return $taxZone->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Soft delete a tax zone.
     *
     * @param TaxZone $taxZone
     * @return void
     */
    public function delete(TaxZone $taxZone): void
    {
        DB::transaction(function () use ($taxZone): void {
            if ($taxZone->is_default) {
                $taxZone->update(['is_default' => false]);
            }

            $taxZone->delete();
            TaxConfigurationUpdated::dispatch('tax_zone');
        });
    }

    /**
     * Soft delete multiple tax zones by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $taxZones = TaxZone::query()->whereIn('id', $ids)->get();
            $deleted = 0;

            foreach ($taxZones as $taxZone) {
                if ($taxZone->is_default) {
                    $taxZone->update(['is_default' => false]);
                }

                $taxZone->delete();
                $deleted++;
            }

            if ($deleted > 0) {
                TaxConfigurationUpdated::dispatch('tax_zone');
            }

            return $deleted;
        });
    }

    /**
     * Permanently delete a tax zone.
     *
     * @param TaxZone $taxZone
     * @return void
     */
    public function forceDelete(TaxZone $taxZone): void
    {
        if ($taxZone->rates()->exists()) {
            throw new DomainException('Cannot permanently delete tax zone with associated rates.');
        }

        DB::transaction(function () use ($taxZone): void {
            $taxZone->forceDelete();
            TaxConfigurationUpdated::dispatch('tax_zone');
        });
    }

    /**
     * Restore a soft-deleted tax zone.
     *
     * @param TaxZone $taxZone
     * @return TaxZone
     */
    public function restore(TaxZone $taxZone): TaxZone
    {
        $taxZone->restore();
        TaxConfigurationUpdated::dispatch('tax_zone');

        return $taxZone->fresh(self::LIST_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted tax zones by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        $count = TaxZone::query()->onlyTrashed()->whereIn('id', $ids)->restore();

        if ($count > 0) {
            TaxConfigurationUpdated::dispatch('tax_zone');
        }

        return $count;
    }

    /**
     * Reorder tax zones by ID.
     *
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                TaxZone::query()->where('id', $id)->update(['sort_order' => $index + 1]);
            }

            TaxConfigurationUpdated::dispatch('tax_zone');
        });
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return EloquentCollection<int, TaxZone>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): EloquentCollection {
        $query = TaxZone::query()->orderBy('sort_order')->latest();

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
     * Get rates for a tax zone.
     *
     * @param TaxZone $taxZone
     * @return EloquentCollection<int, TaxRate>
     */
    public function getRates(TaxZone $taxZone): EloquentCollection
    {
        return $this->find($taxZone->id)->rates;
    }

    /**
     * Set a tax zone as the default.
     *
     * @param TaxZone $taxZone
     * @return TaxZone
     */
    public function setDefault(TaxZone $taxZone): TaxZone
    {
        return DB::transaction(function () use ($taxZone): TaxZone {
            TaxZone::query()->where('is_default', true)->update(['is_default' => false]);
            $taxZone->update(['is_default' => true, 'is_active' => true]);
            TaxConfigurationUpdated::dispatch('tax_zone');

            return $taxZone->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Get the default tax zone.
     *
     * @return TaxZone|null
     */
    public function getDefault(): ?TaxZone
    {
        return TaxZone::query()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Toggle the active status of a tax zone.
     *
     * @param TaxZone $taxZone
     * @return TaxZone
     */
    public function toggleActive(TaxZone $taxZone): TaxZone
    {
        $taxZone->update(['is_active' => ! $taxZone->is_active]);
        TaxConfigurationUpdated::dispatch('tax_zone');

        return $taxZone->fresh(self::LIST_RELATIONS);
    }

    /**
     * Return aggregate counts for the admin dashboard.
     *
     * @return array{total: int, active: int, inactive: int, default: int}
     */
    public function statistics(): array
    {
        return [
            'total' => TaxZone::query()->count(),
            'active' => TaxZone::query()->where('is_active', true)->count(),
            'inactive' => TaxZone::query()->where('is_active', false)->count(),
            'default' => TaxZone::query()->where('is_default', true)->count(),
        ];
    }

    /**
     * Return active tax zones formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return TaxZone::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (TaxZone $taxZone): array => [
                'label' => $taxZone->name,
                'value' => $taxZone->id,
            ]);
    }

    /**
     * Find the best matching tax zone for an address.
     *
     * @param string $country
     * @param string|null $state
     * @param string|null $city
     * @param string|null $postal
     * @return TaxZone|null
     */
    public function findByAddress(
        string $country,
        ?string $state = null,
        ?string $city = null,
        ?string $postal = null,
    ): ?TaxZone {
        $query = TaxZone::query()
            ->where('is_active', true)
            ->where(function ($q) use ($country): void {
                $q->whereNull('country_code')->orWhere('country_code', $country);
            })
            ->orderByRaw('CASE
                WHEN country_code IS NOT NULL AND state IS NOT NULL AND city IS NOT NULL AND postal_code IS NOT NULL THEN 1
                WHEN country_code IS NOT NULL AND state IS NOT NULL AND city IS NOT NULL THEN 2
                WHEN country_code IS NOT NULL AND state IS NOT NULL THEN 3
                WHEN country_code IS NOT NULL THEN 4
                ELSE 5
            END');

        if ($state) {
            $query->where(function ($q) use ($state): void {
                $q->whereNull('state')->orWhere('state', $state);
            });
        }

        if ($city) {
            $query->where(function ($q) use ($city): void {
                $q->whereNull('city')->orWhere('city', $city);
            });
        }

        if ($postal) {
            $query->where(function ($q) use ($postal): void {
                $q->whereNull('postal_code')
                    ->orWhere('postal_code', $postal)
                    ->orWhere(function ($sq) use ($postal): void {
                        $sq->whereNotNull('postal_code_pattern')
                            ->whereRaw('? LIKE postal_code_pattern', [$postal]);
                    });
            });
        }

        return $query->first();
    }

    /**
     * Find a tax zone by geographic coordinates.
     *
     * @param float $lat
     * @param float $lng
     * @return TaxZone|null
     */
    public function findByCoordinates(float $lat, float $lng): ?TaxZone
    {
        return TaxZone::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('radius_km')
            ->whereRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= radius_km',
                [$lat, $lng, $lat]
            )
            ->first();
    }
}
