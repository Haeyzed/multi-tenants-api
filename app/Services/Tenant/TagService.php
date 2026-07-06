<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TagService
{
    /**
     * @param  array<string, mixed>  $filters
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

    public function find(int $id): Tag
    {
        return Tag::query()->findOrFail($id);
    }

    public function findBySlug(string $slug): Tag
    {
        return Tag::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag
    {
        return Tag::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag->fresh();
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Tag::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param  list<int>|null  $ids
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
     * @param  list<int>  $productIds
     */
    public function syncProducts(Tag $tag, array $productIds): void
    {
        $tag->products()->sync($productIds);
        $this->updateProductsCount($tag);
    }

    public function attachProduct(Tag $tag, int $productId): void
    {
        $tag->products()->syncWithoutDetaching([$productId]);
        $this->updateProductsCount($tag);
    }

    public function detachProduct(Tag $tag, int $productId): void
    {
        $tag->products()->detach($productId);
        $this->updateProductsCount($tag);
    }

    public function toggleVisibility(Tag $tag): Tag
    {
        $tag->update(['is_visible' => ! $tag->is_visible]);

        return $tag->fresh();
    }

    public function updateProductsCount(Tag $tag): void
    {
        $count = $tag->products()
            ->where('status', ProductStatus::Active->value)
            ->count();

        $tag->update(['products_count' => $count]);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Tag::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
