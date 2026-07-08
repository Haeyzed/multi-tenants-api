<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages product tags within a tenant store.
 */
class TagService
{
    /**
     * Paginate the tags.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Tag>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Tag::query()
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a tag by ID.
     *
     * @param int $id
     * @return Tag
     */
    public function find(int $id): Tag
    {
        return Tag::query()->findOrFail($id);
    }

    /**
     * Find a tag by slug.
     *
     * @param string $slug
     * @return Tag
     */
    public function findBySlug(string $slug): Tag
    {
        return Tag::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Create a new tag.
     *
     * @param  array<string, mixed>  $data
     * @return Tag
     */
    public function create(array $data): Tag
    {
        return Tag::query()->create($data);
    }

    /**
     * Update a tag.
     *
     * @param Tag $tag
     * @param  array<string, mixed>  $data
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag->fresh();
    }

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     * @return void
     */
    public function delete(Tag $tag): void
    {
        $tag->delete();
    }

    /**
     * Delete multiple tags by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Tag::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection<int, Tag>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Tag::query()->latest();

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
            'total' => Tag::query()->count(),
            'visible' => Tag::query()->where('is_visible', true)->count(),
            'hidden' => Tag::query()->where('is_visible', false)->count(),
        ];
    }

    /**
     * Return visible tags formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Tag::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag) => [
                'label' => $tag->name,
                'value' => $tag->id,
            ]);
    }

    /**
     * Paginate products assigned to the tag.
     *
     * @param Tag $tag
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProducts(Tag $tag, array $filters = []): LengthAwarePaginator
    {
        $query = $tag->products();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    /**
     * Sync products for a tag.
     *
     * @param Tag $tag
     * @param  list<int>  $productIds
     * @return void
     */
    public function syncProducts(Tag $tag, array $productIds): void
    {
        $tag->products()->sync($productIds);
        $this->updateProductsCount($tag);
    }

    /**
     * Attach a product to a tag.
     *
     * @param Tag $tag
     * @param int $productId
     * @return void
     */
    public function attachProduct(Tag $tag, int $productId): void
    {
        $tag->products()->syncWithoutDetaching([$productId]);
        $this->updateProductsCount($tag);
    }

    /**
     * Detach a product from a tag.
     *
     * @param Tag $tag
     * @param int $productId
     * @return void
     */
    public function detachProduct(Tag $tag, int $productId): void
    {
        $tag->products()->detach($productId);
        $this->updateProductsCount($tag);
    }

    /**
     * Flip the tag visibility flag.
     *
     * @param Tag $tag
     * @return Tag
     */
    public function toggleVisibility(Tag $tag): Tag
    {
        $tag->update(['is_visible' => ! $tag->is_visible]);

        return $tag->fresh();
    }

    /**
     * Recalculate and persist the active product count for a tag.
     *
     * @param Tag $tag
     * @return void
     */
    public function updateProductsCount(Tag $tag): void
    {
        $count = $tag->products()
            ->where('status', ProductStatus::Active->value)
            ->count();

        $tag->update(['products_count' => $count]);
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
            Tag::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
