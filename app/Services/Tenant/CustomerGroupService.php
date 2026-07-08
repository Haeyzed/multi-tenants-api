<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\CustomerGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Manages customer groups.
 */
class CustomerGroupService
{
    /**
     * Paginate customer groups.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CustomerGroup::query()
            ->withCount('customers')
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a customer group by ID.
     *
     * @param int $id
     * @return CustomerGroup
     */
    public function find(int $id): CustomerGroup
    {
        return CustomerGroup::query()
            ->withCount('customers')
            ->findOrFail($id);
    }

    /**
     * Create a new customer group.
     *
     * @param array $data
     * @return CustomerGroup
     */
    public function create(array $data): CustomerGroup
    {
        $group = CustomerGroup::query()->create($this->normalizeData($data));

        return $group->fresh()->loadCount('customers');
    }

    /**
     * Update a customer group.
     *
     * @param CustomerGroup $group
     * @param array $data
     * @return CustomerGroup
     */
    public function update(CustomerGroup $group, array $data): CustomerGroup
    {
        $group->update($this->normalizeData($data));

        return $group->fresh()->loadCount('customers');
    }

    /**
     * Delete a customer group.
     *
     * @param CustomerGroup $group
     * @return void
     */
    public function delete(CustomerGroup $group): void
    {
        $group->delete();
    }

    /**
     * Delete multiple customer groups by ID.
     *
     * @param array $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return CustomerGroup::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a customer group permanently.
     *
     * @param CustomerGroup $group
     * @return void
     */
    public function forceDelete(CustomerGroup $group): void
    {
        $group->forceDelete();
    }

    /**
     * Restore a soft-deleted customer group.
     *
     * @param CustomerGroup $group
     * @return CustomerGroup
     */
    public function restore(CustomerGroup $group): CustomerGroup
    {
        $group->restore();

        return $group->fresh()->loadCount('customers');
    }

    /**
     * Restore multiple soft-deleted customer groups by ID.
     *
     * @param array $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return CustomerGroup::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param array|null $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = CustomerGroup::query()->withCount('customers')->latest();

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
     * @return array
     */
    public function statistics(): array
    {
        return [
            'total' => CustomerGroup::query()->count(),
            'active' => CustomerGroup::query()->where('is_active', true)->count(),
            'inactive' => CustomerGroup::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * Return active customer groups formatted for select inputs.
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return CustomerGroup::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CustomerGroup $group) => [
                'label' => $group->name,
                'value' => $group->id,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        if (array_key_exists('discount_percentage', $data)) {
            $data['discount_percent'] = $data['discount_percentage'];
            unset($data['discount_percentage']);
        }

        return $data;
    }
}
