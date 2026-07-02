<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages product brands within a tenant store.
 */
class BrandService
{
    /**
     * Paginate the brands.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Brand::query()
            ->with(['logoMedia', 'bannerMedia'])
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a brand by ID.
     */
    public function find(int $id): Brand
    {
        return Brand::query()
            ->with(['logoMedia', 'bannerMedia'])
            ->findOrFail($id);
    }

    /**
     * Create a new brand.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        $brand = Brand::query()->create($data);

        return $brand->fresh(['logoMedia', 'bannerMedia']);
    }

    /**
     * Update a brand.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->fresh(['logoMedia', 'bannerMedia']);
    }

    /**
     * Delete a brand.
     */
    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    /**
     * Delete multiple brands by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Brand::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a brand permanently.
     */
    public function forceDelete(Brand $brand): void
    {
        $brand->forceDelete();
    }

    /**
     * Restore a soft-deleted brand.
     */
    public function restore(Brand $brand): Brand
    {
        $brand->restore();

        return $brand->fresh(['logoMedia', 'bannerMedia']);
    }

    /**
     * Restore multiple soft-deleted brands by ID.
     *
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return Brand::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * @param  list<int>|null  $ids
     * @return Collection<int, Brand>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Brand::query()->latest();

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
     * @return array{total: int, visible: int, hidden: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Brand::query()->count(),
            'visible' => Brand::query()->where('is_visible', true)->count(),
            'hidden' => Brand::query()->where('is_visible', false)->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Brand::query()
            ->where('is_visible', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand) => [
                'label' => $brand->name,
                'value' => $brand->id,
            ]);
    }
}
