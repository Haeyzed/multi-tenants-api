<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\AttributeSetsExport;
use App\Exports\Tenant\AttributeSetsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreAttributeSetRequest;
use App\Http\Requests\Tenant\UpdateAttributeSetRequest;
use App\Http\Resources\Tenant\AttributeResource;
use App\Http\Resources\Tenant\AttributeSetResource;
use App\Http\Resources\Tenant\CategoryResource;
use App\Imports\Tenant\AttributeSetsImport;
use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeSet;
use App\Services\Tenant\AttributeSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing attribute sets within a tenant store.
 *
 * Exposes CRUD, bulk operations, import/export, statistics, and extension
 * endpoints such as slug lookup, attribute/category sync, and reordering.
 */
class AttributeSetController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly AttributeSetService $attributeSetService,
    ) {}

    /**
     * Get a paginated list of attribute sets.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttributeSet::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attributeSets = $this->attributeSetService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($attributeSets, AttributeSetResource::collection($attributeSets), 'Attribute sets retrieved successfully.');
    }

    /**
     * Create a new attribute set.
     */
    public function store(StoreAttributeSetRequest $request): JsonResponse
    {
        $this->authorize('create', AttributeSet::class);

        $attributeSet = $this->attributeSetService->create($request->validated());

        return $this->created(
            new AttributeSetResource($attributeSet),
            'Attribute set created successfully.',
        );
    }

    /**
     * Get a single attribute set.
     */
    public function show(AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('view', $attribute_set);

        return $this->success(new AttributeSetResource($this->attributeSetService->find($attribute_set->id)), 'Attribute set retrieved successfully.');
    }

    /**
     * Update an existing attribute set.
     */
    public function update(UpdateAttributeSetRequest $request, AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('update', $attribute_set);

        $attributeSet = $this->attributeSetService->update($attribute_set, $request->validated());

        return $this->updated(
            new AttributeSetResource($attributeSet),
            'Attribute set updated successfully.',
        );
    }

    /**
     * Delete an attribute set.
     */
    public function destroy(AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('delete', $attribute_set);

        $this->attributeSetService->delete($attribute_set);

        return $this->deleted('Attribute set deleted successfully.');
    }

    /**
     * Get attribute set options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', AttributeSet::class);

        return $this->success($this->attributeSetService->getOptions(), 'Attribute set options retrieved successfully.');
    }

    /**
     * Get attribute set statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', AttributeSet::class);

        return $this->success($this->attributeSetService->statistics(), 'Attribute set statistics retrieved successfully.');
    }

    /**
     * Delete multiple attribute sets.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', AttributeSet::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:attribute_sets,id'],
        ]);

        $count = $this->attributeSetService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} attribute sets deleted successfully.");
    }

    /**
     * Export attribute sets to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', AttributeSet::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            AttributeSetsExport::availableColumns(),
            ['integer', 'exists:attribute_sets,id'],
        ));

        $attributeSets = $this->attributeSetService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new AttributeSetsExport($attributeSets, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'attribute-sets-export',
            'Attribute Sets Export',
            'Your attribute sets export is attached.',
        );
    }

    /**
     * Download a sample import template for attribute sets.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', AttributeSet::class);

        return $this->importSampleDownload($request, new AttributeSetsImportSample, 'attribute-sets');
    }

    /**
     * Import attribute sets from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', AttributeSet::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new AttributeSetsImport, $request->file('file'));

        return $this->success(null, 'Attribute sets imported successfully.');
    }

    /**
     * Get an attribute set by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $attributeSet = $this->attributeSetService->findBySlug($slug);

        $this->authorize('view', $attributeSet);

        return $this->success(new AttributeSetResource($attributeSet), 'Attribute set retrieved successfully.');
    }

    /**
     * Reorder attribute sets by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', AttributeSet::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attribute_sets,id'],
        ]);

        $this->attributeSetService->reorder($validated['ids']);

        return $this->success(null, 'Attribute sets reordered successfully.');
    }

    /**
     * Get attributes for an attribute set.
     */
    public function attributes(AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('view', $attribute_set);

        $attributes = $this->attributeSetService->getAttributes($attribute_set);

        return $this->success(AttributeResource::collection($attributes), 'Attribute set attributes retrieved successfully.');
    }

    /**
     * Sync attributes for an attribute set.
     */
    public function syncAttributes(Request $request, AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('update', $attribute_set);

        $validated = $request->validate([
            'attribute_ids' => ['required', 'array'],
            'attribute_ids.*' => ['present'],
        ]);

        $this->attributeSetService->syncAttributes($attribute_set, $validated['attribute_ids']);

        return $this->success(
            new AttributeSetResource($this->attributeSetService->find($attribute_set->id)),
            'Attribute set attributes synced successfully.',
        );
    }

    /**
     * Attach an attribute to an attribute set.
     */
    public function attachAttribute(Request $request, AttributeSet $attribute_set, Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute_set);

        $validated = $request->validate([
            'is_required' => ['nullable', 'boolean'],
        ]);

        $this->attributeSetService->attachAttribute(
            $attribute_set,
            $attribute->id,
            (bool) ($validated['is_required'] ?? false),
        );

        return $this->success(
            new AttributeSetResource($this->attributeSetService->find($attribute_set->id)),
            'Attribute attached successfully.',
        );
    }

    /**
     * Detach an attribute from an attribute set.
     */
    public function detachAttribute(AttributeSet $attribute_set, Attribute $attribute): JsonResponse
    {
        $this->authorize('update', $attribute_set);

        $this->attributeSetService->detachAttribute($attribute_set, $attribute->id);

        return $this->success(
            new AttributeSetResource($this->attributeSetService->find($attribute_set->id)),
            'Attribute detached successfully.',
        );
    }

    /**
     * Get categories for an attribute set.
     */
    public function categories(AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('view', $attribute_set);

        $categories = $this->attributeSetService->getCategories($attribute_set);

        return $this->success(CategoryResource::collection($categories), 'Attribute set categories retrieved successfully.');
    }

    /**
     * Sync categories for an attribute set.
     */
    public function syncCategories(Request $request, AttributeSet $attribute_set): JsonResponse
    {
        $this->authorize('update', $attribute_set);

        $validated = $request->validate([
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $this->attributeSetService->syncCategories($attribute_set, $validated['category_ids']);

        return $this->success(
            new AttributeSetResource($this->attributeSetService->find($attribute_set->id)),
            'Attribute set categories synced successfully.',
        );
    }
}
