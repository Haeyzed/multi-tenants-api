<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Models\Tenant\Product;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SupplierAddress;
use App\Models\Tenant\SupplierBankAccount;
use App\Models\Tenant\SupplierContact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Manages product suppliers and their nested contacts, addresses, and bank accounts.
 */
class SupplierService
{
    /**
     * @var list<string>
     */
    private const DETAIL_RELATIONS = ['addresses', 'bankAccounts', 'contacts'];

    /**
     * Paginate suppliers.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Supplier>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('products')
            ->filter($filters)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find a supplier by ID with nested relations.
     *
     * @param int $id
     * @return Supplier
     */
    public function find(int $id): Supplier
    {
        return Supplier::query()
            ->with(self::DETAIL_RELATIONS)
            ->withCount('products')
            ->findOrFail($id);
    }

    /**
     * Find a supplier by slug.
     *
     * @param string $slug
     * @return Supplier
     */
    public function findBySlug(string $slug): Supplier
    {
        return Supplier::query()
            ->with(self::DETAIL_RELATIONS)
            ->withCount('products')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Find a supplier by code.
     *
     * @param string $code
     * @return Supplier
     */
    public function findByCode(string $code): Supplier
    {
        return Supplier::query()
            ->with(self::DETAIL_RELATIONS)
            ->withCount('products')
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * Create a supplier.
     *
     * @param  array<string, mixed>  $data
     * @return Supplier
     */
    public function create(array $data): Supplier
    {
        $supplier = Supplier::query()->create($data);

        if (! empty($data['addresses'])) {
            foreach ($data['addresses'] as $address) {
                $this->addAddress($supplier->id, $address);
            }
        }

        if (! empty($data['bank_accounts'])) {
            foreach ($data['bank_accounts'] as $account) {
                $this->addBankAccount($supplier->id, $account);
            }
        }

        if (! empty($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                $this->addContact($supplier->id, $contact);
            }
        }

        return $this->find($supplier->id);
    }

    /**
     * Update a supplier.
     *
     * @param Supplier $supplier
     * @param  array<string, mixed>  $data
     * @return Supplier
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $this->find($supplier->id);
    }

    /**
     * Soft delete a supplier.
     *
     * @param Supplier $supplier
     * @return void
     */
    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    /**
     * Soft delete multiple suppliers.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Supplier::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Restore a soft-deleted supplier.
     *
     * @param Supplier $supplier
     * @return Supplier
     */
    public function restore(Supplier $supplier): Supplier
    {
        $supplier->restore();

        return $this->find($supplier->id);
    }

    /**
     * Permanently delete a supplier.
     *
     * @param Supplier $supplier
     * @return void
     */
    public function forceDelete(Supplier $supplier): void
    {
        $supplier->forceDelete();
    }

    /**
     * Get supplier statistics.
     *
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => Supplier::query()->count(),
            'active' => Supplier::query()->where('is_active', true)->count(),
            'inactive' => Supplier::query()->where('is_active', false)->count(),
            'with_products' => Supplier::query()->where('products_count', '>', 0)->count(),
        ];
    }

    /**
     * Get supplier options for select inputs.
     *
     * @return SupportCollection<int, array{label: string, value: int, code: string}>
     */
    public function getOptions(): SupportCollection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Supplier $supplier): array => [
                'label' => $supplier->name,
                'value' => $supplier->id,
                'code' => $supplier->code,
            ]);
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection<int, Supplier>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Supplier::query()->latest();

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
     * Toggle supplier active status.
     *
     * @param Supplier $supplier
     * @return Supplier
     */
    public function toggleActive(Supplier $supplier): Supplier
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return $this->find($supplier->id);
    }

    /**
     * Update cached products count for a supplier.
     *
     * @param Supplier $supplier
     * @return Supplier
     */
    public function updateProductsCount(Supplier $supplier): Supplier
    {
        $count = $supplier->products()->where('status', ProductStatus::Active)->count();
        $supplier->update(['products_count' => $count]);

        return $this->find($supplier->id);
    }

    /**
     * Get products for a supplier.
     *
     * @param Supplier $supplier
     * @return Collection<int, Product>
     */
    public function getProducts(Supplier $supplier): Collection
    {
        return $supplier->products()->get();
    }

    // ── Addresses ──

    /**
     * @param Supplier $supplier
     * @return Collection<int, SupplierAddress>
     */
    public function getAddresses(Supplier $supplier): Collection
    {
        return $supplier->addresses()->orderByDesc('is_default')->orderBy('id')->get();
    }

    /**
     * @param int $supplierId
     * @param  array<string, mixed>  $data
     * @return SupplierAddress
     */
    public function addAddress(int $supplierId, array $data): SupplierAddress
    {
        $data['supplier_id'] = $supplierId;

        if ($data['is_default'] ?? false) {
            SupplierAddress::query()
                ->where('supplier_id', $supplierId)
                ->update(['is_default' => false]);
        }

        return SupplierAddress::query()->create($data);
    }

    /**
     * @param SupplierAddress $address
     * @param  array<string, mixed>  $data
     * @return SupplierAddress
     */
    public function updateAddress(SupplierAddress $address, array $data): SupplierAddress
    {
        if (($data['is_default'] ?? false) && ! $address->is_default) {
            SupplierAddress::query()
                ->where('supplier_id', $address->supplier_id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return $address->fresh();
    }

    /**
     * @param SupplierAddress $address
     * @return void
     */
    public function deleteAddress(SupplierAddress $address): void
    {
        $address->delete();
    }

    /**
     * @param SupplierAddress $address
     * @return SupplierAddress
     */
    public function setDefaultAddress(SupplierAddress $address): SupplierAddress
    {
        SupplierAddress::query()
            ->where('supplier_id', $address->supplier_id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return $address->fresh();
    }

    // ── Bank Accounts ──

    /**
     * @param Supplier $supplier
     * @return Collection<int, SupplierBankAccount>
     */
    public function getBankAccounts(Supplier $supplier): Collection
    {
        return $supplier->bankAccounts()->orderByDesc('is_default')->orderBy('id')->get();
    }

    /**
     * @param int $supplierId
     * @param  array<string, mixed>  $data
     * @return SupplierBankAccount
     */
    public function addBankAccount(int $supplierId, array $data): SupplierBankAccount
    {
        $data['supplier_id'] = $supplierId;

        if ($data['is_default'] ?? false) {
            SupplierBankAccount::query()
                ->where('supplier_id', $supplierId)
                ->update(['is_default' => false]);
        }

        return SupplierBankAccount::query()->create($data);
    }

    /**
     * @param SupplierBankAccount $account
     * @param  array<string, mixed>  $data
     * @return SupplierBankAccount
     */
    public function updateBankAccount(SupplierBankAccount $account, array $data): SupplierBankAccount
    {
        if (($data['is_default'] ?? false) && ! $account->is_default) {
            SupplierBankAccount::query()
                ->where('supplier_id', $account->supplier_id)
                ->update(['is_default' => false]);
        }

        $account->update($data);

        return $account->fresh();
    }

    /**
     * @param SupplierBankAccount $account
     * @return void
     */
    public function deleteBankAccount(SupplierBankAccount $account): void
    {
        $account->delete();
    }

    /**
     * @param SupplierBankAccount $account
     * @return SupplierBankAccount
     */
    public function setDefaultBankAccount(SupplierBankAccount $account): SupplierBankAccount
    {
        SupplierBankAccount::query()
            ->where('supplier_id', $account->supplier_id)
            ->update(['is_default' => false]);

        $account->update(['is_default' => true]);

        return $account->fresh();
    }

    // ── Contacts ──

    /**
     * @param Supplier $supplier
     * @return Collection<int, SupplierContact>
     */
    public function getContacts(Supplier $supplier): Collection
    {
        return $supplier->contacts()->orderByDesc('is_primary')->orderBy('id')->get();
    }

    /**
     * @param int $supplierId
     * @param  array<string, mixed>  $data
     * @return SupplierContact
     */
    public function addContact(int $supplierId, array $data): SupplierContact
    {
        $data['supplier_id'] = $supplierId;

        if ($data['is_primary'] ?? false) {
            SupplierContact::query()
                ->where('supplier_id', $supplierId)
                ->update(['is_primary' => false]);
        }

        return SupplierContact::query()->create($data);
    }

    /**
     * @param SupplierContact $contact
     * @param  array<string, mixed>  $data
     * @return SupplierContact
     */
    public function updateContact(SupplierContact $contact, array $data): SupplierContact
    {
        if (($data['is_primary'] ?? false) && ! $contact->is_primary) {
            SupplierContact::query()
                ->where('supplier_id', $contact->supplier_id)
                ->update(['is_primary' => false]);
        }

        $contact->update($data);

        return $contact->fresh();
    }

    /**
     * @param SupplierContact $contact
     * @return void
     */
    public function deleteContact(SupplierContact $contact): void
    {
        $contact->delete();
    }

    /**
     * @param SupplierContact $contact
     * @return SupplierContact
     */
    public function setPrimaryContact(SupplierContact $contact): SupplierContact
    {
        SupplierContact::query()
            ->where('supplier_id', $contact->supplier_id)
            ->update(['is_primary' => false]);

        $contact->update(['is_primary' => true]);

        return $contact->fresh();
    }
}
