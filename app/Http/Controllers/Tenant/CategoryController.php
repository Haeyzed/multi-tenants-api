<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\CategoriesExport;
use App\Exports\Tenant\CategoriesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreCategoryRequest;
use App\Http\Requests\Tenant\UpdateCategoryRequest;
use App\Http\Resources\Tenant\CategoryResource;
use App\Http\Resources\Tenant\ProductResource;
use App\Imports\Tenant\CategoriesImport;
use App\Models\Tenant\AttributeSet;
use App\Models\Tenant\Category;
use App\Services\Tenant\CategoryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manages product categories within a tenant store API.
 */
class CategoryController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Get a paginated list of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'array'],
            'is_visible.*' => ['string', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $categories = $this->categoryService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($categories, CategoryResource::collection($categories), 'Categories retrieved successfully.');
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->categoryService->create($request->validated());

        return $this->created(
            new CategoryResource($category),
            'Category created successfully.',
        );
    }

    /**
     * Get a single category.
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return $this->success(new CategoryResource($this->categoryService->find($category->id)), 'Category retrieved successfully.');
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->update($category, $request->validated());

        return $this->updated(
            new CategoryResource($category),
            'Category updated successfully.',
        );
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        try {
            $this->categoryService->delete($category);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Category deleted successfully.');
    }

    /**
     * Get category options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->success($this->categoryService->getOptions(), 'Category options retrieved successfully.');
    }

    /**
     * Get category statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->success($this->categoryService->statistics(), 'Category statistics retrieved successfully.');
    }

    /**
     * Delete multiple categories.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $count = $this->categoryService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} categories deleted successfully.");
    }

    /**
     * Export categories to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            CategoriesExport::availableColumns(),
            ['integer', 'exists:categories,id'],
        ));

        $categories = $this->categoryService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new CategoriesExport($categories, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'categories-export',
            'Categories Export',
            'Your categories export is attached.',
        );
    }

    /**
     * Download a sample import template for categories.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Category::class);

        return $this->importSampleDownload($request, new CategoriesImportSample, 'categories');
    }

    /**
     * Import categories from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new CategoriesImport, $request->file('file'));

        return $this->success(null, 'Categories imported successfully.');
    }

    /**
     * Force delete a category permanently.
     */
    public function forceDestroy(Category $category): JsonResponse
    {
        $this->authorize('forceDelete', $category);

        $this->categoryService->forceDelete($category);

        return $this->deleted('Category permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted category.
     */
    public function restore(Category $category): JsonResponse
    {
        $this->authorize('restore', $category);

        $category = $this->categoryService->restore($category);

        return $this->success(
            new CategoryResource($category),
            'Category restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted categories.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->categoryService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} categories restored successfully.");
    }

    /**
     * Get a category by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug);

        $this->authorize('view', $category);

        return $this->success(new CategoryResource($category), 'Category retrieved successfully.');
    }

    /**
     * Get the category tree.
     */
    public function tree(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->success(
            ['tree' => $this->categoryService->getTree()],
            'Category tree retrieved successfully.',
        );
    }

    /**
     * Get hierarchical category options for select inputs.
     */
    public function treeForSelect(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->success(
            $this->categoryService->getTreeForSelect(),
            'Category tree options retrieved successfully.',
        );
    }

    /**
     * Get breadcrumb trail for a category.
     */
    public function breadcrumbs(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $breadcrumbs = $this->categoryService->getBreadcrumbs($category);

        return $this->success(
            CategoryResource::collection($breadcrumbs),
            'Category breadcrumbs retrieved successfully.',
        );
    }

    /**
     * Get visible child categories.
     */
    public function children(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $children = $this->categoryService->getChildren($category->id);

        return $this->success(
            CategoryResource::collection($children),
            'Category children retrieved successfully.',
        );
    }

    /**
     * Get all descendant categories.
     */
    public function descendants(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $descendants = $this->categoryService->getDescendants($category);

        return $this->success(
            CategoryResource::collection($descendants),
            'Category descendants retrieved successfully.',
        );
    }

    /**
     * Move a category under a new parent.
     */
    public function move(Request $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', 'not_in:'.$category->id],
        ]);

        $category = $this->categoryService->move($category, $validated['parent_id'] ?? null);

        return $this->updated(new CategoryResource($category), 'Category moved successfully.');
    }

    /**
     * Get products for a category.
     */
    public function products(Request $request, Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $filters = $request->validate([
            'is_visible' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->categoryService->getProducts($category, $filters);

        return $this->paginated(
            $products,
            ProductResource::collection($products),
            'Category products retrieved successfully.',
        );
    }

    /**
     * Toggle category visibility.
     */
    public function toggleVisibility(Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->toggleVisibility($category);

        return $this->updated(new CategoryResource($category), 'Category visibility toggled successfully.');
    }

    /**
     * Toggle category featured status.
     */
    public function toggleFeatured(Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->toggleFeatured($category);

        return $this->updated(new CategoryResource($category), 'Category featured status toggled successfully.');
    }

    /**
     * Recalculate and update the category products count.
     */
    public function updateProductsCount(Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $this->categoryService->updateProductsCount($category);

        return $this->success(
            new CategoryResource($category->fresh()),
            'Category products count updated successfully.',
        );
    }

    /**
     * Reorder categories by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $this->categoryService->reorder($validated['ids']);

        return $this->success(null, 'Categories reordered successfully.');
    }

    /**
     * Sync attribute sets for a category.
     */
    public function syncAttributeSets(Request $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'attribute_set_ids' => ['required', 'array'],
            'attribute_set_ids.*' => ['integer', 'exists:attribute_sets,id'],
        ]);

        $this->categoryService->syncAttributeSets($category, $validated['attribute_set_ids']);

        return $this->success(
            CategoryResource::make($this->categoryService->find($category->id)),
            'Category attribute sets synced successfully.',
        );
    }

    /**
     * Assign an attribute set to a category.
     */
    public function assignAttributeSet(Request $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'attribute_set_id' => ['required', 'integer', 'exists:attribute_sets,id'],
        ]);

        $this->categoryService->assignAttributeSet($category, $validated['attribute_set_id']);

        return $this->success(
            CategoryResource::make($this->categoryService->find($category->id)),
            'Attribute set assigned successfully.',
        );
    }

    /**
     * Remove an attribute set from a category.
     */
    public function removeAttributeSet(Category $category, AttributeSet $attributeSet): JsonResponse
    {
        $this->authorize('update', $category);

        $this->categoryService->removeAttributeSet($category, $attributeSet->id);

        return $this->success(
            CategoryResource::make($this->categoryService->find($category->id)),
            'Attribute set removed successfully.',
        );
    }
}
