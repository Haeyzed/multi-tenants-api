<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\AttributesExport;
use App\Exports\Tenant\AttributesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreAttributeRequest;
use App\Http\Requests\Tenant\StoreAttributeValueRequest;
use App\Http\Requests\Tenant\UpdateAttributeRequest;
use App\Http\Requests\Tenant\UpdateAttributeValueRequest;
use App\Http\Resources\Tenant\AttributeResource;
use App\Http\Resources\Tenant\AttributeValueResource;
use App\Imports\Tenant\AttributesImport;
use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeValue;
use App\Services\Tenant\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing product attributes within a tenant store.
 *
 * Exposes CRUD, bulk operations, import/export, statistics, value management,
 * and extension endpoints such as slug/code lookup, toggles, and reordering.
 */
class AttributeController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly AttributeService $attributeService,
    ) {}

    /**
     * Get a paginated list of attributes.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_variant' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string'],
        ]);

        $attributes = $this->attributeService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($attributes, AttributeResource::collection($attributes), 'Attributes retrieved successfully.');
    }

    /**
     * Create a new attribute.
     */
    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $this->authorize('create', Attribute::class);

        $attribute = $this->attributeService->create($request->validated());

        return $this->created(
            new AttributeResource($attribute),
            'Attribute created successfully.',
        );
    }

    /**
     * Get a single attribute.
     */
    public function show(Attribute $attribute): JsonResponse
    {
        $this->authorize('view', $attribute);

        return $this->success(new AttributeResource($this->attributeService->find($attribute->id)), 'Attribute retrieved successfully.');
    }

    /**
     * Update an existing attribute.
     */
    public function update(UpdateAttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute);

        $attribute = $this->attributeService->update($attribute, $request->validated());

        return $this->updated(
            new AttributeResource($attribute),
            'Attribute updated successfully.',
        );
    }

    /**
     * Soft delete an attribute.
     */
    public function destroy(Attribute $attribute): JsonResponse
    {
        $this->authorize('delete', $attribute);

        $this->attributeService->delete($attribute);

        return $this->deleted('Attribute deleted successfully.');
    }

    /**
     * Get attribute options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        return $this->success($this->attributeService->getOptions(), 'Attribute options retrieved successfully.');
    }

    /**
     * Get attribute statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        return $this->success($this->attributeService->statistics(), 'Attribute statistics retrieved successfully.');
    }

    /**
     * Delete multiple attributes.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Attribute::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:attributes,id'],
        ]);

        $count = $this->attributeService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} attributes deleted successfully.");
    }

    /**
     * Export attributes to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Attribute::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            AttributesExport::availableColumns(),
            ['integer', 'exists:attributes,id'],
        ));

        $attributes = $this->attributeService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new AttributesExport($attributes, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'attributes-export',
            'Attributes Export',
            'Your attributes export is attached.',
        );
    }

    /**
     * Download a sample import template for attributes.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Attribute::class);

        return $this->importSampleDownload($request, new AttributesImportSample, 'attributes');
    }

    /**
     * Import attributes from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Attribute::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new AttributesImport, $request->file('file'));

        return $this->success(null, 'Attributes imported successfully.');
    }

    /**
     * Force delete an attribute permanently.
     */
    public function forceDestroy(Attribute $attribute): JsonResponse
    {
        $this->authorize('forceDelete', $attribute);

        $this->attributeService->forceDelete($attribute);

        return $this->deleted('Attribute permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted attribute.
     */
    public function restore(Attribute $attribute): JsonResponse
    {
        $this->authorize('restore', $attribute);

        $attribute = $this->attributeService->restore($attribute);

        return $this->success(
            new AttributeResource($attribute),
            'Attribute restored successfully.',
        );
    }

    /**
     * Restore multiple soft-deleted attributes.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Attribute::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->attributeService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} attributes restored successfully.");
    }

    /**
     * Get an attribute by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $attribute = $this->attributeService->findBySlug($slug);

        $this->authorize('view', $attribute);

        return $this->success(new AttributeResource($attribute), 'Attribute retrieved successfully.');
    }

    /**
     * Get an attribute by code.
     */
    public function showByCode(string $code): JsonResponse
    {
        $attribute = $this->attributeService->findByCode($code);

        $this->authorize('view', $attribute);

        return $this->success(new AttributeResource($attribute), 'Attribute retrieved successfully.');
    }

    /**
     * Get filterable attributes.
     */
    public function filterable(): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        $attributes = $this->attributeService->getFilterable();

        return $this->success(AttributeResource::collection($attributes), 'Filterable attributes retrieved successfully.');
    }

    /**
     * Get variant attributes.
     */
    public function variantAttributes(): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);

        $attributes = $this->attributeService->getVariantAttributes();

        return $this->success(AttributeResource::collection($attributes), 'Variant attributes retrieved successfully.');
    }

    /**
     * Toggle attribute filterable status.
     */
    public function toggleFilterable(Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute);

        $attribute = $this->attributeService->toggleFilterable($attribute);

        return $this->updated(new AttributeResource($attribute), 'Attribute filterable status toggled successfully.');
    }

    /**
     * Toggle attribute variant status.
     */
    public function toggleVariant(Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute);

        $attribute = $this->attributeService->toggleVariant($attribute);

        return $this->updated(new AttributeResource($attribute), 'Attribute variant status toggled successfully.');
    }

    /**
     * Reorder attributes by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Attribute::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attributes,id'],
        ]);

        $this->attributeService->reorder($validated['ids']);

        return $this->success(null, 'Attributes reordered successfully.');
    }

    /**
     * Get values for an attribute.
     */
    public function values(Attribute $attribute): JsonResponse
    {
        $this->authorize('view', $attribute);

        $values = $this->attributeService->getValues($attribute);

        return $this->success(AttributeValueResource::collection($values), 'Attribute values retrieved successfully.');
    }

    /**
     * Create a value for an attribute.
     */
    public function storeValue(StoreAttributeValueRequest $request, Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute);

        $value = $this->attributeService->createValue($attribute, $request->validated());

        return $this->created(
            new AttributeValueResource($value),
            'Attribute value created successfully.',
        );
    }

    /**
     * Update an attribute value.
     */
    public function updateValue(UpdateAttributeValueRequest $request, Attribute $attribute, AttributeValue $value): JsonResponse
    {
        $this->authorize('update', $attribute);

        abort_if($value->attribute_id !== $attribute->id, 404);

        $value = $this->attributeService->updateValue($value, $request->validated());

        return $this->updated(
            new AttributeValueResource($value),
            'Attribute value updated successfully.',
        );
    }

    /**
     * Delete an attribute value.
     */
    public function destroyValue(Attribute $attribute, AttributeValue $value): JsonResponse
    {
        $this->authorize('update', $attribute);

        abort_if($value->attribute_id !== $attribute->id, 404);

        $this->attributeService->deleteValue($value);

        return $this->deleted('Attribute value deleted successfully.');
    }

    /**
     * Reorder attribute values by ID list.
     */
    public function reorderValues(Request $request, Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attribute_values,id'],
        ]);

        $this->attributeService->reorderValues($attribute, $validated['ids']);

        return $this->success(null, 'Attribute values reordered successfully.');
    }
}
