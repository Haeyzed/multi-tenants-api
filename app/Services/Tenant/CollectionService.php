<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Collection;
use App\Models\Tenant\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Manages product collections within a tenant store.
 */
class CollectionService
{
    /**
     * @var list<string>
     */
    private const array MEDIA_RELATIONS = ['image', 'banner'];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Collection>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Collection::query()
            ->withCount('products')
            ->with(self::MEDIA_RELATIONS)
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a collection by ID.
     *
     * @param int $id
     * @return Collection
     */
    public function find(int $id): Collection
    {
        return Collection::query()
            ->with(['products', ...self::MEDIA_RELATIONS])
            ->findOrFail($id);
    }

    /**
     * Find a collection by slug.
     *
     * @param string $slug
     * @return Collection
     */
    public function findBySlug(string $slug): Collection
    {
        return Collection::query()
            ->with(['products', ...self::MEDIA_RELATIONS])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Create a new collection.
     *
     * @param  array<string, mixed>  $data
     * @return Collection
     */
    public function create(array $data): Collection
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $productIds = $data['product_ids'] ?? null;
        unset($data['product_ids']);

        $collection = Collection::query()->create($data);

        if (! empty($productIds)) {
            $this->syncProducts($collection, $productIds);
        }

        return $collection->fresh(['products', ...self::MEDIA_RELATIONS]);
    }

    /**
     * Update a collection.
     *
     * @param Collection $collection
     * @param  array<string, mixed>  $data
     * @return Collection
     */
    public function update(Collection $collection, array $data): Collection
    {
        $productIds = $data['product_ids'] ?? null;
        unset($data['product_ids']);

        $collection->update($data);

        if ($productIds !== null) {
            $this->syncProducts($collection, $productIds);
        }

        return $collection->fresh(['products', ...self::MEDIA_RELATIONS]);
    }

    /**
     * Delete a collection.
     *
     * @param Collection $collection
     * @return void
     */
    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    /**
     * Delete multiple collections by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Collection::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Restore a soft-deleted collection.
     *
     * @param Collection $collection
     * @return Collection
     */
    public function restore(Collection $collection): Collection
    {
        $collection->restore();

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted collections by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Collection::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Force delete a collection permanently.
     *
     * @param Collection $collection
     * @return void
     */
    public function forceDelete(Collection $collection): void
    {
        $collection->forceDelete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return SupportCollection<int, Collection>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): SupportCollection {
        $query = Collection::query()->latest();

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
     * @return array{total: int, visible: int, featured: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Collection::query()->count(),
            'visible' => Collection::query()->where('is_visible', true)->count(),
            'featured' => Collection::query()->where('is_featured', true)->count(),
        ];
    }

    /**
     * Return collections formatted for select inputs.
     *
     * @return SupportCollection<int, array{label: string, value: int}>
     */
    public function getOptions(): SupportCollection
    {
        return Collection::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Collection $collection) => [
                'label' => $collection->name,
                'value' => $collection->id,
            ]);
    }

    /**
     * Get products for a collection.
     *
     * @param Collection $collection
     * @return EloquentCollection<int, Product>
     */
    public function getProducts(Collection $collection): EloquentCollection
    {
        return $collection->products;
    }

    /**
     * Sync products for a collection.
     *
     * @param Collection $collection
     * @param  list<int>  $productIds
     * @return void
     */
    public function syncProducts(Collection $collection, array $productIds): void
    {
        $syncData = [];

        foreach ($productIds as $index => $productId) {
            $syncData[$productId] = ['sort_order' => $index];
        }

        $collection->products()->sync($syncData);
    }

    /**
     * Add a product to a collection.
     *
     * @param Collection $collection
     * @param int $productId
     * @return void
     */
    public function addProduct(Collection $collection, int $productId): void
    {
        $collection->products()->syncWithoutDetaching([$productId]);
    }

    /**
     * Remove a product from a collection.
     *
     * @param Collection $collection
     * @param int $productId
     * @return void
     */
    public function removeProduct(Collection $collection, int $productId): void
    {
        $collection->products()->detach($productId);
    }

    /**
     * Reorder products within a collection.
     *
     * @param Collection $collection
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorderProducts(Collection $collection, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $productId) {
            DB::table('collection_products')
                ->where('collection_id', $collection->id)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index]);
        }
    }

    /**
     * Refresh automated collection products.
     *
     * @param Collection $collection
     * @return void
     */
    public function refreshAutomated(Collection $collection): void
    {
        if (! $collection->is_automated) {
            return;
        }

        $conditions = $collection->conditions ?? [];
        $query = Product::query();

        foreach ($conditions as $condition) {
            $this->applyCondition($query, $condition);
        }

        $productIds = $query->pluck('id')->toArray();
        $this->syncProducts($collection, $productIds);
    }

    /**
     * Toggle the visibility of a collection.
     *
     * @param Collection $collection
     * @return Collection
     */
    public function toggleVisibility(Collection $collection): Collection
    {
        $collection->update(['is_visible' => ! $collection->is_visible]);

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Toggle the featured status of a collection.
     *
     * @param Collection $collection
     * @return Collection
     */
    public function toggleFeatured(Collection $collection): Collection
    {
        $collection->update(['is_featured' => ! $collection->is_featured]);

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Reorder collections.
     *
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Collection::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Apply a condition to a product query.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $condition
     * @return void
     */
    private function applyCondition($query, array $condition): void
    {
        $field = $condition['field'] ?? 'name';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        match ($field) {
            'category_id' => $query->where('category_id', $operator, $value),
            'brand_id' => $query->where('brand_id', $operator, $value),
            'price' => $query->where('price', $operator, $value),
            'tag' => $query->whereHas('tags', fn ($q) => $q->where('tags.id', $value)),
            default => $query->where($field, $operator, $value),
        };
    }
}
