<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Models\Tenant\Brand;
use App\Models\Tenant\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages product brands within a tenant store.
 *
 * Coordinates brand listing, CRUD, soft-delete lifecycle, export queries,
 * product relationships, visibility/featured toggles, and sort reordering.
 * Slugs are generated automatically by the {@see Brand} model.
 */
class BrandService
{
    /**
     * @var list<string>
     */
    private const array MEDIA_RELATIONS = ['logoMedia', 'bannerMedia'];

    /**
     * Paginate the brands.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Brand::query()
            ->with(self::MEDIA_RELATIONS)
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a brand by ID.
     *
     * @param int $id
     * @return Brand
     */
    public function find(int $id): Brand
    {
        return Brand::query()
            ->with(self::MEDIA_RELATIONS)
            ->findOrFail($id);
    }

    /**
     * Find a brand by slug.
     *
     * @param string $slug
     * @return Brand
     */
    public function findBySlug(string $slug): Brand
    {
        return Brand::query()
            ->with(self::MEDIA_RELATIONS)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Create a new brand.
     *
     * @param  array<string, mixed>  $data
     * @return Brand
     */
    public function create(array $data): Brand
    {
        $brand = Brand::query()->create($data);

        return $brand->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Update a brand.
     *
     * @param Brand $brand
     * @param  array<string, mixed>  $data
     * @return Brand
     */
    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Delete a brand.
     *
     * @param Brand $brand
     * @return void
     */
    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    /**
     * Delete multiple brands by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Brand::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a brand permanently.
     *
     * @param Brand $brand
     * @return void
     */
    public function forceDelete(Brand $brand): void
    {
        $brand->forceDelete();
    }

    /**
     * Restore a soft-deleted brand.
     *
     * @param Brand $brand
     * @return Brand
     */
    public function restore(Brand $brand): Brand
    {
        $brand->restore();

        return $brand->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted brands by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Brand::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
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
     * Return aggregate counts for the admin dashboard.
     *
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
     * Return visible brands formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Brand::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand) => [
                'label' => $brand->name,
                'value' => $brand->id,
            ]);
    }

    /**
     * Paginate products assigned to the brand.
     *
     * @param Brand $brand
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProducts(Brand $brand, array $filters = []): LengthAwarePaginator
    {
        $query = $brand->products();

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', $filters['is_visible']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    /**
     * Flip the brand visibility flag.
     *
     * @param Brand $brand
     * @return Brand
     */
    public function toggleVisibility(Brand $brand): Brand
    {
        $brand->update(['is_visible' => ! $brand->is_visible]);

        return $brand->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Flip the brand featured flag.
     *
     * @param Brand $brand
     * @return Brand
     */
    public function toggleFeatured(Brand $brand): Brand
    {
        $brand->update(['is_featured' => ! $brand->is_featured]);

        return $brand->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Recalculate and persist the active product count for a brand.
     *
     * @param Brand $brand
     * @return void
     */
    public function updateProductsCount(Brand $brand): void
    {
        $count = $brand->products()
            ->where('status', ProductStatus::Active->value)
            ->count();

        $brand->update(['products_count' => $count]);
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
            Brand::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
