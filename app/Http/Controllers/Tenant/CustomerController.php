<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\CustomersExport;
use App\Exports\Tenant\CustomersImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreCustomerRequest;
use App\Http\Requests\Tenant\UpdateCustomerRequest;
use App\Http\Resources\Tenant\CustomerResource;
use App\Imports\Tenant\CustomersImport;
use App\Models\Tenant\Customer;
use App\Services\Tenant\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Manages customers within a tenant store.
 */
class CustomerController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Get a paginated list of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
            'customer_group_id' => ['nullable', 'integer'],
        ]);

        $customers = $this->customerService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($customers, CustomerResource::collection($customers), 'Customers retrieved successfully.');
    }

    /**
     * Create a new customer.
     *
     * @throws Throwable
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->create($request->validated());

        return $this->created(
            new CustomerResource($customer),
            'Customer created successfully.',
        );
    }

    /**
     * Get a single customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(new CustomerResource($this->customerService->find($customer->id)), 'Customer retrieved successfully.');
    }

    /**
     * Update an existing customer.
     *
     * @throws Throwable
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->update($customer, $request->validated());

        return $this->updated(
            new CustomerResource($customer),
            'Customer updated successfully.',
        );
    }

    /**
     * Delete a customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $this->customerService->delete($customer);

        return $this->deleted('Customer deleted successfully.');
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        return $this->success($this->customerService->getOptions(), 'Customer options retrieved successfully.');
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        return $this->success($this->customerService->statistics(), 'Customer statistics retrieved successfully.');
    }

    /**
     * Delete multiple customers.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Customer::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:customers,id'],
        ]);

        $count = $this->customerService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} customers deleted successfully.");
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            CustomersExport::availableColumns(),
            ['integer', 'exists:customers,id'],
        ));

        $customers = $this->customerService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new CustomersExport($customers, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'customers-export',
            'Customers Export',
            'Your customers export is attached.',
        );
    }

    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Customer::class);

        return $this->importSampleDownload($request, new CustomersImportSample, 'customers');
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new CustomersImport, $request->file('file'));

        return $this->success(null, 'Customers imported successfully.');
    }

    /**
     * Force delete a customer permanently.
     */
    public function forceDestroy(Customer $customer): JsonResponse
    {
        $this->authorize('forceDelete', $customer);

        $this->customerService->forceDelete($customer);

        return $this->deleted('Customer permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore(Customer $customer): JsonResponse
    {
        $this->authorize('restore', $customer);

        $customer = $this->customerService->restore($customer);

        return $this->success(
            new CustomerResource($customer),
            'Customer restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted customers.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Customer::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->customerService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} customers restored successfully.");
    }
}
