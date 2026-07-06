<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AttributeService
{
    /**
     * @param  array<string, mixed>  $filters
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

    public function find(int $id): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function findByCode(string $code): Attribute
    {
        return Attribute::query()
            ->with(['values'])
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
     */
    public function update(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($data);

        return $attribute->fresh(['values']);
    }

    public function delete(Attribute $attribute): void
    {
        $attribute->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return Attribute::query()->whereIn('id', $ids)->delete();
    }

    public function restore(Attribute $attribute): Attribute
    {
        $attribute->restore();

        return $attribute->fresh(['values']);
    }

    /**
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return Attribute::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function forceDelete(Attribute $attribute): void
    {
        $attribute->forceDelete();
    }

    /**
     * @param  list<int>|null  $ids
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
     * @return EloquentCollection<int, AttributeValue>
     */
    public function getValues(Attribute $attribute): EloquentCollection
    {
        return $attribute->values;
    }

    /**
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
     */
    public function updateValue(AttributeValue $value, array $data): AttributeValue
    {
        $value->update($data);

        return $value->fresh();
    }

    public function deleteValue(AttributeValue $value): void
    {
        $value->delete();
    }

    /**
     * @param  list<int>  $orderedIds
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

    public function toggleFilterable(Attribute $attribute): Attribute
    {
        $attribute->update(['is_filterable' => ! $attribute->is_filterable]);

        return $attribute->fresh(['values']);
    }

    public function toggleVariant(Attribute $attribute): Attribute
    {
        $attribute->update(['is_variant' => ! $attribute->is_variant]);

        return $attribute->fresh(['values']);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Attribute::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
