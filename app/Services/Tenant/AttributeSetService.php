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

/**
 * Manages attribute sets, which group attributes for products.
 *
 * This service handles the CRUD operations for attribute sets, manages the
 * relationship between attribute sets and attributes, and provides methods
 * for syncing and reordering.
 */
class AttributeSetService
{
    /**
     * Paginate attribute sets.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
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

    /**
     * Find an attribute set by ID.
     *
     * @param int $id
     * @return AttributeSet
     */
    public function find(int $id): AttributeSet
    {
        return AttributeSet::query()
            ->with(['attributes', 'categories'])
            ->findOrFail($id);
    }

    /**
     * Find an attribute set by slug.
     *
     * @param string $slug
     * @return AttributeSet
     */
    public function findBySlug(string $slug): AttributeSet
    {
        return AttributeSet::query()
            ->with(['attributes', 'categories'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Create a new attribute set.
     *
     * @param  array<string, mixed>  $data
     * @return AttributeSet
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
     * Update an attribute set.
     *
     * @param AttributeSet $set
     * @param  array<string, mixed>  $data
     * @return AttributeSet
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

    /**
     * Delete an attribute set.
     *
     * @param AttributeSet $set
     * @return void
     */
    public function delete(AttributeSet $set): void
    {
        $set->delete();
    }

    /**
     * Delete multiple attribute sets by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return AttributeSet::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
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
     * Return aggregate counts for the admin dashboard.
     *
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
     * Return attribute sets formatted for select inputs.
     *
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
     * Get attributes for an attribute set.
     *
     * @param AttributeSet $set
     * @return EloquentCollection<int, Attribute>
     */
    public function getAttributes(AttributeSet $set): EloquentCollection
    {
        return $set->attributes()->with('values')->get();
    }

    /**
     * Sync attributes for an attribute set.
     *
     * @param AttributeSet $set
     * @param  list<int|array{id: int, is_required?: bool, sort_order?: int}>  $attributeIds
     * @return void
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

    /**
     * Attach an attribute to an attribute set.
     *
     * @param AttributeSet $set
     * @param int $attributeId
     * @param bool $isRequired
     * @return void
     */
    public function attachAttribute(AttributeSet $set, int $attributeId, bool $isRequired = false): void
    {
        $set->attributes()->syncWithoutDetaching([
            $attributeId => ['is_required' => $isRequired],
        ]);
    }

    /**
     * Detach an attribute from an attribute set.
     *
     * @param AttributeSet $set
     * @param int $attributeId
     * @return void
     */
    public function detachAttribute(AttributeSet $set, int $attributeId): void
    {
        $set->attributes()->detach($attributeId);
    }

    /**
     * Get categories for an attribute set.
     *
     * @param AttributeSet $set
     * @return EloquentCollection<int, Category>
     */
    public function getCategories(AttributeSet $set): EloquentCollection
    {
        return $set->categories;
    }

    /**
     * Sync categories for an attribute set.
     *
     * @param AttributeSet $set
     * @param  list<int>  $categoryIds
     * @return void
     */
    public function syncCategories(AttributeSet $set, array $categoryIds): void
    {
        $set->categories()->sync($categoryIds);
    }

    /**
     * Reorder attribute sets.
     *
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            AttributeSet::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
