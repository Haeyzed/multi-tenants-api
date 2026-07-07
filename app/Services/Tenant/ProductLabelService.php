<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\ProductLabel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductLabelService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ProductLabel>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ProductLabel::query()
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ProductLabel
    {
        return ProductLabel::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductLabel
    {
        return ProductLabel::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductLabel $productLabel, array $data): ProductLabel
    {
        $productLabel->update($data);

        return $productLabel->fresh();
    }

    public function delete(ProductLabel $productLabel): void
    {
        $productLabel->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return ProductLabel::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param  list<int>|null  $ids
     * @return Collection<int, ProductLabel>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = ProductLabel::query()->latest();

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
            'total' => ProductLabel::query()->count(),
            'active' => ProductLabel::query()->where('is_active', true)->count(),
            'inactive' => ProductLabel::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return ProductLabel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProductLabel $label) => [
                'label' => $label->name,
                'value' => $label->id,
            ]);
    }

    public function toggleActive(ProductLabel $productLabel): ProductLabel
    {
        $productLabel->update(['is_active' => ! $productLabel->is_active]);

        return $productLabel->fresh();
    }
}
