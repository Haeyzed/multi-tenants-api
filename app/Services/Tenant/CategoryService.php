<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages product categories within a tenant store.
 */
class CategoryService
{
    /**
     * Paginate the categories.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with(['parent', 'imageMedia', 'bannerMedia'])
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a category by ID.
     */
    public function find(int $id): Category
    {
        return Category::query()
            ->with(['parent', 'children', 'imageMedia', 'bannerMedia'])
            ->findOrFail($id);
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $category = Category::query()->create($data);

        return $category->fresh(['imageMedia', 'bannerMedia']);
    }

    /**
     * Update a category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh(['imageMedia', 'bannerMedia']);
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * Delete multiple categories by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Category::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a category permanently.
     */
    public function forceDelete(Category $category): void
    {
        $category->forceDelete();
    }

    /**
     * Restore a soft-deleted category.
     */
    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->fresh(['imageMedia', 'bannerMedia']);
    }

    /**
     * Restore multiple soft-deleted categories by ID.
     *
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return Category::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * @param  list<int>|null  $ids
     * @return Collection<int, Category>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Category::query()->orderBy('sort_order')->latest();

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
     * @return array{total: int, visible: int, hidden: int, root: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Category::query()->count(),
            'visible' => Category::query()->where('is_visible', true)->count(),
            'hidden' => Category::query()->where('is_visible', false)->count(),
            'root' => Category::query()->whereNull('parent_id')->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Category::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'label' => $category->name,
                'value' => $category->id,
            ]);
    }
}
