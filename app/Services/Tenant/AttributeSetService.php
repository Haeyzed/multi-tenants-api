<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeSet;
use App\Models\Tenant\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AttributeSetService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, AttributeSet>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return AttributeSet::query()
            ->withCount(['attributes', 'categories'])
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): AttributeSet
    {
        return AttributeSet::query()
            ->with(['attributes', 'categories'])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): AttributeSet
    {
        return AttributeSet::query()
            ->with(['attributes', 'categories'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AttributeSet
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $attributeIds = $data['attribute_ids'] ?? null;
        unset($data['attribute_ids']);

        $set = AttributeSet::query()->create($data);

        if (! empty($attributeIds)) {
            $this->syncAttributes($set, $attributeIds);
        }

        return $set->fresh(['attributes', 'categories']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AttributeSet $set, array $data): AttributeSet
    {
        $attributeIds = $data['attribute_ids'] ?? null;
        unset($data['attribute_ids']);

        $set->update($data);

        if ($attributeIds !== null) {
            $this->syncAttributes($set, $attributeIds);
        }

        return $set->fresh(['attributes', 'categories']);
    }

    public function delete(AttributeSet $set): void
    {
        $set->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return AttributeSet::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param  list<int>|null  $ids
     * @return Collection<int, AttributeSet>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = AttributeSet::query()->latest();

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
     * @return array{total: int, active: int, inactive: int}
     */
    public function statistics(): array
    {
        return [
            'total' => AttributeSet::query()->count(),
            'active' => AttributeSet::query()->where('is_active', true)->count(),
            'inactive' => AttributeSet::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return AttributeSet::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (AttributeSet $set) => [
                'label' => $set->name,
                'value' => $set->id,
            ]);
    }

    /**
     * @return EloquentCollection<int, Attribute>
     */
    public function getAttributes(AttributeSet $set): EloquentCollection
    {
        return $set->attributes()->with('values')->get();
    }

    /**
     * @param  list<int|array{id: int, is_required?: bool, sort_order?: int}>  $attributeIds
     */
    public function syncAttributes(AttributeSet $set, array $attributeIds): void
    {
        $syncData = [];

        foreach ($attributeIds as $index => $value) {
            if (is_array($value)) {
                $syncData[$value['id']] = [
                    'is_required' => $value['is_required'] ?? false,
                    'sort_order' => $value['sort_order'] ?? ($index + 1),
                ];
            } else {
                $syncData[$value] = [
                    'is_required' => false,
                    'sort_order' => $index + 1,
                ];
            }
        }

        $set->attributes()->sync($syncData);
    }

    public function attachAttribute(AttributeSet $set, int $attributeId, bool $isRequired = false): void
    {
        $set->attributes()->syncWithoutDetaching([
            $attributeId => ['is_required' => $isRequired],
        ]);
    }

    public function detachAttribute(AttributeSet $set, int $attributeId): void
    {
        $set->attributes()->detach($attributeId);
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    public function getCategories(AttributeSet $set): EloquentCollection
    {
        return $set->categories;
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(AttributeSet $set, array $categoryIds): void
    {
        $set->categories()->sync($categoryIds);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            AttributeSet::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
