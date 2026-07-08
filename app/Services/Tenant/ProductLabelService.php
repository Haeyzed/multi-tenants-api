<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\ProductLabel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages product labels, which can be used to highlight products with
 * special offers or characteristics (e.g., "New", "Sale", "Bestseller").
 */
class ProductLabelService
{
    /**
     * Paginate product labels.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
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

    /**
     * Find a product label by ID.
     *
     * @param int $id
     * @return ProductLabel
     */
    public function find(int $id): ProductLabel
    {
        return ProductLabel::query()->findOrFail($id);
    }

    /**
     * Create a new product label.
     *
     * @param  array<string, mixed>  $data
     * @return ProductLabel
     */
    public function create(array $data): ProductLabel
    {
        return ProductLabel::query()->create($data);
    }

    /**
     * Update a product label.
     *
     * @param ProductLabel $productLabel
     * @param  array<string, mixed>  $data
     * @return ProductLabel
     */
    public function update(ProductLabel $productLabel, array $data): ProductLabel
    {
        $productLabel->update($data);

        return $productLabel->fresh();
    }

    /**
     * Delete a product label.
     *
     * @param ProductLabel $productLabel
     * @return void
     */
    public function delete(ProductLabel $productLabel): void
    {
        $productLabel->delete();
    }

    /**
     * Delete multiple product labels by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return ProductLabel::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
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
     * Return aggregate counts for the admin dashboard.
     *
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
     * Return active product labels formatted for select inputs.
     *
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

    /**
     * Toggle the active status of a product label.
     *
     * @param ProductLabel $productLabel
     * @return ProductLabel
     */
    public function toggleActive(ProductLabel $productLabel): ProductLabel
    {
        $productLabel->update(['is_active' => ! $productLabel->is_active]);

        return $productLabel->fresh();
    }
}
