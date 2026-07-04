<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\ProductsExport;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreProductRequest;
use App\Http\Requests\Tenant\StoreProductVariantRequest;
use App\Http\Requests\Tenant\UpdateProductRequest;
use App\Http\Requests\Tenant\UpdateProductVariantRequest;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\ProductVariantResource;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages products within a tenant store API.
 */
class ProductController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', 'in:draft,active,archived'],
            'is_visible' => ['nullable', 'array'],
            'is_visible.*' => ['string', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'array'],
            'is_featured.*' => ['string', 'in:featured,not_featured'],
            'product_type' => ['nullable', 'array'],
            'product_type.*' => ['string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'has_variants' => ['nullable', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ]);

        $products = $this->productService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $products,
            ProductResource::collection($products),
            'Products retrieved successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->create($request->validated());

        return $this->created(
            new ProductResource($product),
            'Product created successfully.',
        );
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            new ProductResource($this->productService->find($product->id)),
            'Product retrieved successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->update($product, $request->validated());

        return $this->updated(
            new ProductResource($product),
            'Product updated successfully.',
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return $this->deleted('Product deleted successfully.');
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->success(
            $this->productService->getOptions(),
            'Product options retrieved successfully.',
        );
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->success(
            $this->productService->statistics(),
            'Product statistics retrieved successfully.',
        );
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $count = $this->productService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} products deleted successfully.");
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            ProductsExport::availableColumns(),
            ['integer', 'exists:products,id'],
        ));

        $products = $this->productService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new ProductsExport($products, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'products-export',
            'Products Export',
            'Your products export is attached.',
        );
    }

    /**
     * @throws Throwable
     */
    public function storeVariant(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $variant = $this->productService->createVariant($product, $request->validated());

        return $this->created(
            new ProductVariantResource($variant),
            'Product variant created successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function updateVariant(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
    ): JsonResponse {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $variant = $this->productService->updateVariant($variant, $request->validated());

        return $this->updated(
            new ProductVariantResource($variant),
            'Product variant updated successfully.',
        );
    }

    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $this->productService->deleteVariant($variant);

        return $this->deleted('Product variant deleted successfully.');
    }

    public function forceDestroy(Product $product): JsonResponse
    {
        $this->authorize('forceDelete', $product);

        $this->productService->forceDelete($product);

        return $this->deleted('Product permanently deleted successfully.');
    }

    public function restore(Product $product): JsonResponse
    {
        $this->authorize('restore', $product);

        $product = $this->productService->restore($product);

        return $this->success(
            new ProductResource($this->productService->find($product->id)),
            'Product restored successfully.',
        );
    }

    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->productService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} products restored successfully.");
    }
}
