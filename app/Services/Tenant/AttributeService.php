<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Manages product attributes and their values.
 */
class AttributeService
{
    /**
     * Paginate attributes.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Attribute>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Attribute::query()
            ->withCount('values')
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find an attribute by ID.
     *
     * @param int $id
     * @return Attribute
     */
    public function find(int $id): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->findOrFail($id);
    }

    /**
     * Find an attribute by slug.
     *
     * @param string $slug
     * @return Attribute
     */
    public function findBySlug(string $slug): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Find an attribute by code.
     *
     * @param string $code
     * @return Attribute
     */
    public function findByCode(string $code): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * Create a new attribute.
     *
     * @param  array<string, mixed>  $data
     * @return Attribute
     */
    public function create(array $data): Attribute
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (empty($data['code']) && ! empty($data['name'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        return Attribute::query()->create($data);
    }

    /**
     * Update an attribute.
     *
     * @param Attribute $attribute
     * @param  array<string, mixed>  $data
     * @return Attribute
     */
    public function update(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($data);

        return $attribute->fresh(['values']);
    }

    /**
     * Delete an attribute.
     *
     * @param Attribute $attribute
     * @return void
     */
    public function delete(Attribute $attribute): void
    {
        $attribute->delete();
    }

    /**
     * Delete multiple attributes by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Attribute::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Restore a soft-deleted attribute.
     *
     * @param Attribute $attribute
     * @return Attribute
     */
    public function restore(Attribute $attribute): Attribute
    {
        $attribute->restore();

        return $attribute->fresh(['values']);
    }

    /**
     * Restore multiple soft-deleted attributes by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Attribute::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Force delete an attribute permanently.
     *
     * @param Attribute $attribute
     * @return void
     */
    public function forceDelete(Attribute $attribute): void
    {
        $attribute->forceDelete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection<int, Attribute>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Attribute::query()->latest();

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
     * @return array{total: int, filterable: int, variant: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Attribute::query()->count(),
            'filterable' => Attribute::query()->where('is_filterable', true)->count(),
            'variant' => Attribute::query()->where('is_variant', true)->count(),
        ];
    }

    /**
     * Return attributes formatted for select inputs.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Attribute::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Attribute $attribute) => [
                'label' => $attribute->name,
                'value' => $attribute->id,
            ]);
    }

    /**
     * Get values for an attribute.
     *
     * @param Attribute $attribute
     * @return EloquentCollection<int, AttributeValue>
     */
    public function getValues(Attribute $attribute): EloquentCollection
    {
        return $attribute->values;
    }

    /**
     * Create a new value for an attribute.
     *
     * @param Attribute $attribute
     * @param  array<string, mixed>  $data
     * @return AttributeValue
     */
    public function createValue(Attribute $attribute, array $data): AttributeValue
    {
        $data['attribute_id'] = $attribute->id;

        if (empty($data['slug']) && ! empty($data['value'])) {
            $data['slug'] = Str::slug($data['value']);
        }

        return AttributeValue::query()->create($data);
    }

    /**
     * Update an attribute value.
     *
     * @param AttributeValue $value
     * @param  array<string, mixed>  $data
     * @return AttributeValue
     */
    public function updateValue(AttributeValue $value, array $data): AttributeValue
    {
        $value->update($data);

        return $value->fresh();
    }

    /**
     * Delete an attribute value.
     *
     * @param AttributeValue $value
     * @return void
     */
    public function deleteValue(AttributeValue $value): void
    {
        $value->delete();
    }

    /**
     * Reorder attribute values.
     *
     * @param Attribute $attribute
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorderValues(Attribute $attribute, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            AttributeValue::query()
                ->where('id', $id)
                ->where('attribute_id', $attribute->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Get filterable attributes.
     *
     * @return EloquentCollection<int, Attribute>
     */
    public function getFilterable(): EloquentCollection
    {
        return Attribute::query()
            ->where('is_filterable', true)
            ->with(['values'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get variant attributes.
     *
     * @return EloquentCollection<int, Attribute>
     */
    public function getVariantAttributes(): EloquentCollection
    {
        return Attribute::query()
            ->where('is_variant', true)
            ->with(['values'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Toggle the filterable flag for an attribute.
     *
     * @param Attribute $attribute
     * @return Attribute
     */
    public function toggleFilterable(Attribute $attribute): Attribute
    {
        $attribute->update(['is_filterable' => ! $attribute->is_filterable]);

        return $attribute->fresh(['values']);
    }

    /**
     * Toggle the variant flag for an attribute.
     *
     * @param Attribute $attribute
     * @return Attribute
     */
    public function toggleVariant(Attribute $attribute): Attribute
    {
        $attribute->update(['is_variant' => ! $attribute->is_variant]);

        return $attribute->fresh(['values']);
    }

    /**
     * Reorder attributes.
     *
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Attribute::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
