<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\SuppliersExport;
use App\Exports\Tenant\SuppliersImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreSupplierAddressRequest;
use App\Http\Requests\Tenant\StoreSupplierBankAccountRequest;
use App\Http\Requests\Tenant\StoreSupplierContactRequest;
use App\Http\Requests\Tenant\StoreSupplierRequest;
use App\Http\Requests\Tenant\UpdateSupplierAddressRequest;
use App\Http\Requests\Tenant\UpdateSupplierBankAccountRequest;
use App\Http\Requests\Tenant\UpdateSupplierContactRequest;
use App\Http\Requests\Tenant\UpdateSupplierRequest;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\SupplierAddressResource;
use App\Http\Resources\Tenant\SupplierBankAccountResource;
use App\Http\Resources\Tenant\SupplierContactResource;
use App\Http\Resources\Tenant\SupplierResource;
use App\Imports\Tenant\SuppliersImport;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SupplierAddress;
use App\Models\Tenant\SupplierBankAccount;
use App\Models\Tenant\SupplierContact;
use App\Services\Tenant\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * HTTP API for managing product suppliers within a tenant store.
 */
class SupplierController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    /**
     * Get a paginated list of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $suppliers = $this->supplierService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $suppliers,
            SupplierResource::collection($suppliers),
            'Suppliers retrieved successfully.',
        );
    }

    /**
     * Create a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = $this->supplierService->create($request->validated());

        return $this->created(
            new SupplierResource($supplier),
            'Supplier created successfully.',
        );
    }

    /**
     * Get a single supplier.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return $this->success(
            new SupplierResource($this->supplierService->find($supplier->id)),
            'Supplier retrieved successfully.',
        );
    }

    /**
     * Update an existing supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->update($supplier, $request->validated());

        return $this->updated(
            new SupplierResource($supplier),
            'Supplier updated successfully.',
        );
    }

    /**
     * Soft delete a supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->supplierService->delete($supplier);

        return $this->deleted('Supplier deleted successfully.');
    }

    /**
     * Get supplier statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        return $this->success(
            $this->supplierService->statistics(),
            'Supplier statistics retrieved successfully.',
        );
    }

    /**
     * Get supplier options for select inputs.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        return $this->success(
            $this->supplierService->getOptions(),
            'Supplier options retrieved successfully.',
        );
    }

    /**
     * Get a supplier by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $supplier = $this->supplierService->findBySlug($slug);
        $this->authorize('view', $supplier);

        return $this->success(
            new SupplierResource($supplier),
            'Supplier retrieved successfully.',
        );
    }

    /**
     * Get a supplier by code.
     */
    public function showByCode(string $code): JsonResponse
    {
        $supplier = $this->supplierService->findByCode($code);
        $this->authorize('view', $supplier);

        return $this->success(
            new SupplierResource($supplier),
            'Supplier retrieved successfully.',
        );
    }

    /**
     * Delete multiple suppliers.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Supplier::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:suppliers,id'],
        ]);

        $deleted = $this->supplierService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} supplier(s) deleted successfully.",
        );
    }

    /**
     * Export suppliers to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            SuppliersExport::availableColumns(),
            ['integer', 'exists:suppliers,id'],
        ));

        $suppliers = $this->supplierService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new SuppliersExport($suppliers, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'suppliers-export',
            'Suppliers Export',
            'Your suppliers export is attached.',
        );
    }

    /**
     * Download a sample import template for suppliers.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Supplier::class);

        return $this->importSampleDownload($request, new SuppliersImportSample, 'suppliers');
    }

    /**
     * Import suppliers from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new SuppliersImport, $request->file('file'));

        return $this->success(null, 'Suppliers imported successfully.');
    }

    /**
     * Permanently delete a supplier.
     */
    public function forceDestroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('forceDelete', $supplier);

        $this->supplierService->forceDelete($supplier);

        return $this->deleted('Supplier permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted supplier.
     */
    public function restore(Supplier $supplier): JsonResponse
    {
        $this->authorize('restore', $supplier);

        $supplier = $this->supplierService->restore($supplier);

        return $this->success(
            new SupplierResource($supplier),
            'Supplier restored successfully.',
        );
    }

    /**
     * Toggle supplier active status.
     */
    public function toggleActive(Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->toggleActive($supplier);

        return $this->updated(
            new SupplierResource($supplier),
            'Supplier status updated successfully.',
        );
    }

    /**
     * Update products count for a supplier.
     */
    public function updateProductsCount(Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->updateProductsCount($supplier);

        return $this->updated(
            new SupplierResource($supplier),
            'Supplier products count updated successfully.',
        );
    }

    /**
     * Get products for a supplier.
     */
    public function products(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $products = $this->supplierService->getProducts($supplier);

        return $this->success(
            ProductResource::collection($products),
            'Supplier products retrieved successfully.',
        );
    }

    // ── Contacts ──

    /**
     * List contacts for a supplier.
     */
    public function contacts(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return $this->success(
            SupplierContactResource::collection($this->supplierService->getContacts($supplier)),
            'Supplier contacts retrieved successfully.',
        );
    }

    /**
     * Create a contact for a supplier.
     */
    public function storeContact(StoreSupplierContactRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $contact = $this->supplierService->addContact($supplier->id, $request->validated());

        return $this->created(
            new SupplierContactResource($contact),
            'Supplier contact created successfully.',
        );
    }

    /**
     * Update a supplier contact.
     */
    public function updateContact(
        UpdateSupplierContactRequest $request,
        Supplier $supplier,
        SupplierContact $contact,
    ): JsonResponse {
        $this->authorize('update', $supplier);
        $this->ensureContactBelongsToSupplier($supplier, $contact);

        $contact = $this->supplierService->updateContact($contact, $request->validated());

        return $this->updated(
            new SupplierContactResource($contact),
            'Supplier contact updated successfully.',
        );
    }

    /**
     * Delete a supplier contact.
     */
    public function destroyContact(Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureContactBelongsToSupplier($supplier, $contact);

        $this->supplierService->deleteContact($contact);

        return $this->deleted('Supplier contact deleted successfully.');
    }

    /**
     * Set a contact as primary for a supplier.
     */
    public function setPrimaryContact(Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureContactBelongsToSupplier($supplier, $contact);

        $contact = $this->supplierService->setPrimaryContact($contact);

        return $this->updated(
            new SupplierContactResource($contact),
            'Primary supplier contact updated successfully.',
        );
    }

    // ── Addresses ──

    /**
     * List addresses for a supplier.
     */
    public function addresses(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return $this->success(
            SupplierAddressResource::collection($this->supplierService->getAddresses($supplier)),
            'Supplier addresses retrieved successfully.',
        );
    }

    /**
     * Create an address for a supplier.
     */
    public function storeAddress(StoreSupplierAddressRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $address = $this->supplierService->addAddress($supplier->id, $request->validated());

        return $this->created(
            new SupplierAddressResource($address),
            'Supplier address created successfully.',
        );
    }

    /**
     * Update a supplier address.
     */
    public function updateAddress(
        UpdateSupplierAddressRequest $request,
        Supplier $supplier,
        SupplierAddress $address,
    ): JsonResponse {
        $this->authorize('update', $supplier);
        $this->ensureAddressBelongsToSupplier($supplier, $address);

        $address = $this->supplierService->updateAddress($address, $request->validated());

        return $this->updated(
            new SupplierAddressResource($address),
            'Supplier address updated successfully.',
        );
    }

    /**
     * Delete a supplier address.
     */
    public function destroyAddress(Supplier $supplier, SupplierAddress $address): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureAddressBelongsToSupplier($supplier, $address);

        $this->supplierService->deleteAddress($address);

        return $this->deleted('Supplier address deleted successfully.');
    }

    /**
     * Set an address as default for a supplier.
     */
    public function setDefaultAddress(Supplier $supplier, SupplierAddress $address): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureAddressBelongsToSupplier($supplier, $address);

        $address = $this->supplierService->setDefaultAddress($address);

        return $this->updated(
            new SupplierAddressResource($address),
            'Default supplier address updated successfully.',
        );
    }

    // ── Bank Accounts ──

    /**
     * List bank accounts for a supplier.
     */
    public function bankAccounts(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return $this->success(
            SupplierBankAccountResource::collection($this->supplierService->getBankAccounts($supplier)),
            'Supplier bank accounts retrieved successfully.',
        );
    }

    /**
     * Create a bank account for a supplier.
     */
    public function storeBankAccount(StoreSupplierBankAccountRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $account = $this->supplierService->addBankAccount($supplier->id, $request->validated());

        return $this->created(
            new SupplierBankAccountResource($account),
            'Supplier bank account created successfully.',
        );
    }

    /**
     * Update a supplier bank account.
     */
    public function updateBankAccount(
        UpdateSupplierBankAccountRequest $request,
        Supplier $supplier,
        SupplierBankAccount $bankAccount,
    ): JsonResponse {
        $this->authorize('update', $supplier);
        $this->ensureBankAccountBelongsToSupplier($supplier, $bankAccount);

        $account = $this->supplierService->updateBankAccount($bankAccount, $request->validated());

        return $this->updated(
            new SupplierBankAccountResource($account),
            'Supplier bank account updated successfully.',
        );
    }

    /**
     * Delete a supplier bank account.
     */
    public function destroyBankAccount(Supplier $supplier, SupplierBankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureBankAccountBelongsToSupplier($supplier, $bankAccount);

        $this->supplierService->deleteBankAccount($bankAccount);

        return $this->deleted('Supplier bank account deleted successfully.');
    }

    /**
     * Set a bank account as default for a supplier.
     */
    public function setDefaultBankAccount(Supplier $supplier, SupplierBankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureBankAccountBelongsToSupplier($supplier, $bankAccount);

        $account = $this->supplierService->setDefaultBankAccount($bankAccount);

        return $this->updated(
            new SupplierBankAccountResource($account),
            'Default supplier bank account updated successfully.',
        );
    }

    private function ensureContactBelongsToSupplier(Supplier $supplier, SupplierContact $contact): void
    {
        if ($contact->supplier_id !== $supplier->id) {
            throw new NotFoundHttpException('Supplier contact not found.');
        }
    }

    private function ensureAddressBelongsToSupplier(Supplier $supplier, SupplierAddress $address): void
    {
        if ($address->supplier_id !== $supplier->id) {
            throw new NotFoundHttpException('Supplier address not found.');
        }
    }

    private function ensureBankAccountBelongsToSupplier(Supplier $supplier, SupplierBankAccount $bankAccount): void
    {
        if ($bankAccount->supplier_id !== $supplier->id) {
            throw new NotFoundHttpException('Supplier bank account not found.');
        }
    }
}
