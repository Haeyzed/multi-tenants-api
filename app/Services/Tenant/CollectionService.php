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

class CollectionService
{
    /**
     * @var list<string>
     */
    private const MEDIA_RELATIONS = ['image', 'banner'];

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

    public function find(int $id): Collection
    {
        return Collection::query()
            ->with(['products', ...self::MEDIA_RELATIONS])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): Collection
    {
        return Collection::query()
            ->with(['products', ...self::MEDIA_RELATIONS])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
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

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Collection::query()->whereIn('id', $ids)->delete();
    }

    public function restore(Collection $collection): Collection
    {
        $collection->restore();

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return Collection::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function forceDelete(Collection $collection): void
    {
        $collection->forceDelete();
    }

    /**
     * @param  list<int>|null  $ids
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
     * @return EloquentCollection<int, Product>
     */
    public function getProducts(Collection $collection): EloquentCollection
    {
        return $collection->products;
    }

    /**
     * @param  list<int>  $productIds
     */
    public function syncProducts(Collection $collection, array $productIds): void
    {
        $syncData = [];

        foreach ($productIds as $index => $productId) {
            $syncData[$productId] = ['sort_order' => $index];
        }

        $collection->products()->sync($syncData);
    }

    public function addProduct(Collection $collection, int $productId): void
    {
        $collection->products()->syncWithoutDetaching([$productId]);
    }

    public function removeProduct(Collection $collection, int $productId): void
    {
        $collection->products()->detach($productId);
    }

    /**
     * @param  list<int>  $orderedIds
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

    public function toggleVisibility(Collection $collection): Collection
    {
        $collection->update(['is_visible' => ! $collection->is_visible]);

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    public function toggleFeatured(Collection $collection): Collection
    {
        $collection->update(['is_featured' => ! $collection->is_featured]);

        return $collection->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Collection::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $condition
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
