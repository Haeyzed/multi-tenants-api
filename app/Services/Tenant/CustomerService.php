<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages customers within a tenant store.
 */
class CustomerService
{
    /**
     * Paginate customers.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['group'])
            ->latest()
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Find a customer by ID.
     *
     * @param int $id
     * @return Customer
     */
    public function find(int $id): Customer
    {
        return Customer::query()
            ->with(['group', 'tags', 'addresses'])
            ->findOrFail($id);
    }

    /**
     * Create a new customer.
     *
     * @param array $data
     * @return Customer
     * @throws Throwable
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $customer = Customer::query()->create($data);

            if (is_array($tagIds)) {
                $customer->tags()->sync($tagIds);
            }

            return $customer->fresh(['group', 'tags', 'addresses']);
        });
    }

    /**
     * Update a customer.
     *
     * @param Customer $customer
     * @param array $data
     * @return Customer
     * @throws Throwable
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $customer->update($data);

            if (is_array($tagIds)) {
                $customer->tags()->sync($tagIds);
            }

            return $customer->fresh(['group', 'tags', 'addresses']);
        });
    }

    /**
     * Delete a customer.
     *
     * @param Customer $customer
     * @return void
     */
    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    /**
     * Delete multiple customers by ID.
     *
     * @param array $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Customer::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a customer permanently.
     *
     * @param Customer $customer
     * @return void
     */
    public function forceDelete(Customer $customer): void
    {
        $customer->forceDelete();
    }

    /**
     * Restore a soft-deleted customer.
     *
     * @param Customer $customer
     * @return Customer
     */
    public function restore(Customer $customer): Customer
    {
        $customer->restore();

        return $customer->fresh(['group', 'tags', 'addresses']);
    }

    /**
     * Restore multiple soft-deleted customers by ID.
     *
     * @param array $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Customer::query()->onlyTrashed()->whereIn('id', $ids)->restore();
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
        $query = Customer::query()->with('group')->latest();

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
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('is_active', true)->count(),
            'inactive' => Customer::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * Return active customers formatted for select inputs.
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Customer $customer) => [
                'label' => $customer->fullName(),
                'value' => $customer->id,
            ]);
    }
}
