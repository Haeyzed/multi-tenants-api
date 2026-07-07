<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use App\Exports\Tenant\ProductsExport;
use App\Exports\Tenant\ProductsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Controllers\Tenant\Concerns\ImportsSpreadsheets;
use App\Http\Requests\Tenant\AnswerProductQuestionRequest;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\GenerateProductVariantsRequest;
use App\Http\Requests\Tenant\StoreProductDocumentRequest;
use App\Http\Requests\Tenant\StoreProductFaqRequest;
use App\Http\Requests\Tenant\StoreProductRequest;
use App\Http\Requests\Tenant\StoreProductVariantRequest;
use App\Http\Requests\Tenant\SyncProductBundleItemsRequest;
use App\Http\Requests\Tenant\SyncProductDownloadsRequest;
use App\Http\Requests\Tenant\SyncProductOptionsRequest;
use App\Http\Requests\Tenant\SyncProductRelationsRequest;
use App\Http\Requests\Tenant\SyncProductServiceRequest;
use App\Http\Requests\Tenant\SyncProductSubscriptionRequest;
use App\Http\Requests\Tenant\SyncProductSuppliersRequest;
use App\Http\Requests\Tenant\SyncProductVideosRequest;
use App\Http\Requests\Tenant\UpdateProductDocumentRequest;
use App\Http\Requests\Tenant\UpdateProductFaqRequest;
use App\Http\Requests\Tenant\UpdateProductRequest;
use App\Http\Requests\Tenant\UpdateProductReviewRequest;
use App\Http\Requests\Tenant\UpdateProductVariantRequest;
use App\Http\Resources\Tenant\ProductBundleResource;
use App\Http\Resources\Tenant\ProductDocumentResource;
use App\Http\Resources\Tenant\ProductDownloadResource;
use App\Http\Resources\Tenant\ProductFaqResource;
use App\Http\Resources\Tenant\ProductOptionResource;
use App\Http\Resources\Tenant\ProductProviderResource;
use App\Http\Resources\Tenant\ProductQuestionResource;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\ProductReviewResource;
use App\Http\Resources\Tenant\ProductServiceResource;
use App\Http\Resources\Tenant\ProductSubscriptionResource;
use App\Http\Resources\Tenant\ProductSupplierResource;
use App\Http\Resources\Tenant\ProductVariantResource;
use App\Imports\Tenant\ProductsImport;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductDocument;
use App\Models\Tenant\ProductFaq;
use App\Models\Tenant\ProductQuestion;
use App\Models\Tenant\ProductReview;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Manages products within a tenant store API.
 */
class ProductController extends ApiController
{
    use ExportsSpreadsheets, ImportsSpreadsheets;

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
            'primary_category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in(array_column(ProductStatus::cases(), 'value'))],
            'visibility' => ['nullable', 'array'],
            'visibility.*' => ['string', Rule::in(ProductVisibility::values())],
            'is_featured' => ['nullable', 'array'],
            'is_featured.*' => ['string', 'in:featured,not_featured'],
            'type' => ['nullable', 'array'],
            'type.*' => ['string', Rule::in(ProductType::values())],
            'condition' => ['nullable', 'array'],
            'condition.*' => ['string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'has_variants' => ['nullable', 'boolean'],
            'track_inventory' => ['nullable', 'boolean'],
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

    public function updateMany(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:products,id'],
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'visibility' => ['sometimes', new Enum(ProductVisibility::class)],
        ]);

        $count = $this->productService->updateMany(
            $validated['ids'],
            $validated,
        );

        return $this->success(null, "{$count} products updated successfully.");
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
     * Download a sample import template for products.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        return $this->importSampleDownload($request, new ProductsImportSample, 'products');
    }

    /**
     * Import products from Excel.
     *
     * @throws Throwable
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        return $this->runSpreadsheetImport(
            new ProductsImport($this->productService),
            $request->file('file'),
            'Products imported successfully.',
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

    /**
     * @throws Throwable
     */
    public function syncOptions(SyncProductOptionsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductOptions(
            $product,
            $request->validated('options'),
        );

        return $this->success(
            ProductOptionResource::collection($product->options),
            'Product options synced successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function syncSuppliers(SyncProductSuppliersRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductSuppliers(
            $product,
            $request->validated('suppliers'),
        );

        return $this->success(
            ProductSupplierResource::collection($product->productSuppliers),
            'Product suppliers synced successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function syncRelations(SyncProductRelationsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductRelations($product, $request->validated());

        return $this->success([
            'related_product_ids' => $product->relatedProducts->pluck('related_product_id')->values(),
            'cross_sell_product_ids' => $product->crossSellProducts->pluck('related_product_id')->values(),
            'up_sell_product_ids' => $product->upSellProducts->pluck('related_product_id')->values(),
            'related_products' => ProductResource::collection(
                $product->relatedProducts->pluck('relatedProduct')->filter(),
            ),
            'cross_sell_products' => ProductResource::collection(
                $product->crossSellProducts->pluck('relatedProduct')->filter(),
            ),
            'up_sell_products' => ProductResource::collection(
                $product->upSellProducts->pluck('relatedProduct')->filter(),
            ),
        ], 'Product relations synced successfully.');
    }

    /**
     * @throws Throwable
     */
    public function syncDownloads(SyncProductDownloadsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductDownloads(
            $product,
            $request->validated('downloads'),
        );

        return $this->success(
            ProductDownloadResource::collection($product->downloads),
            'Product downloads synced successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function syncBundleItems(SyncProductBundleItemsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductBundleItems(
            $product,
            $request->validated('bundle_items'),
        );

        return $this->success(
            ProductBundleResource::collection($product->bundleItems),
            'Product bundle items synced successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function syncService(SyncProductServiceRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductService($product, $request->validated());

        return $this->success([
            'service' => new ProductServiceResource($product->service),
            'providers' => ProductProviderResource::collection($product->providers),
        ], 'Product service synced successfully.');
    }

    /**
     * @throws Throwable
     */
    public function syncSubscription(SyncProductSubscriptionRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncProductSubscription(
            $product,
            $request->validated('subscription'),
        );

        return $this->success(
            new ProductSubscriptionResource($product->subscription),
            'Product subscription synced successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function generateVariants(GenerateProductVariantsRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $variants = $this->productService->generateVariantsFromOptions(
            $product,
            $request->validated(),
        );

        return $this->created(
            ProductVariantResource::collection($variants),
            "{$variants->count()} variants generated successfully.",
        );
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

    /**
     * @throws Throwable
     */
    public function duplicate(Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $copy = $this->productService->duplicate($product);

        return $this->created(
            new ProductResource($copy),
            'Product duplicated successfully.',
        );
    }

    // ── FAQs ──

    public function faqs(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            ProductFaqResource::collection($this->productService->getFaqs($product)),
            'Product FAQs retrieved successfully.',
        );
    }

    public function storeFaq(StoreProductFaqRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $faq = $this->productService->addFaq($product->id, $request->validated());

        return $this->created(
            new ProductFaqResource($faq),
            'Product FAQ created successfully.',
        );
    }

    public function updateFaq(
        UpdateProductFaqRequest $request,
        Product $product,
        ProductFaq $faq,
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->ensureFaqBelongsToProduct($product, $faq);

        $faq = $this->productService->updateFaq($faq, $request->validated());

        return $this->updated(
            new ProductFaqResource($faq),
            'Product FAQ updated successfully.',
        );
    }

    public function destroyFaq(Product $product, ProductFaq $faq): JsonResponse
    {
        $this->authorize('update', $product);
        $this->ensureFaqBelongsToProduct($product, $faq);

        $this->productService->deleteFaq($faq);

        return $this->deleted('Product FAQ deleted successfully.');
    }

    // ── Documents ──

    public function documents(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            ProductDocumentResource::collection($this->productService->getDocuments($product)),
            'Product documents retrieved successfully.',
        );
    }

    public function storeDocument(StoreProductDocumentRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $document = $this->productService->addDocument($product->id, $request->validated());

        return $this->created(
            new ProductDocumentResource($document),
            'Product document created successfully.',
        );
    }

    public function updateDocument(
        UpdateProductDocumentRequest $request,
        Product $product,
        ProductDocument $document,
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->ensureDocumentBelongsToProduct($product, $document);

        $document = $this->productService->updateDocument($document, $request->validated());

        return $this->updated(
            new ProductDocumentResource($document),
            'Product document updated successfully.',
        );
    }

    public function destroyDocument(Product $product, ProductDocument $document): JsonResponse
    {
        $this->authorize('update', $product);
        $this->ensureDocumentBelongsToProduct($product, $document);

        $this->productService->deleteDocument($document);

        return $this->deleted('Product document deleted successfully.');
    }

    // ── Reviews ──

    public function reviews(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            ProductReviewResource::collection($this->productService->getReviews($product)),
            'Product reviews retrieved successfully.',
        );
    }

    public function updateReview(
        UpdateProductReviewRequest $request,
        Product $product,
        ProductReview $review,
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->ensureReviewBelongsToProduct($product, $review);

        $review = $this->productService->updateReview($review, $request->validated());

        return $this->updated(
            new ProductReviewResource($review),
            'Product review updated successfully.',
        );
    }

    public function destroyReview(Product $product, ProductReview $review): JsonResponse
    {
        $this->authorize('update', $product);
        $this->ensureReviewBelongsToProduct($product, $review);

        $this->productService->deleteReview($review);

        return $this->deleted('Product review deleted successfully.');
    }

    // ── Questions ──

    public function questions(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            ProductQuestionResource::collection($this->productService->getQuestions($product)),
            'Product questions retrieved successfully.',
        );
    }

    public function updateQuestion(
        AnswerProductQuestionRequest $request,
        Product $product,
        ProductQuestion $question,
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->ensureQuestionBelongsToProduct($product, $question);

        $question = $this->productService->answerQuestion($question, $request->validated());

        return $this->updated(
            new ProductQuestionResource($question),
            'Product question answered successfully.',
        );
    }

    public function destroyQuestion(Product $product, ProductQuestion $question): JsonResponse
    {
        $this->authorize('update', $product);
        $this->ensureQuestionBelongsToProduct($product, $question);

        $this->productService->deleteQuestion($question);

        return $this->deleted('Product question deleted successfully.');
    }

    /**
     * @throws Throwable
     */
    public function syncVideos(SyncProductVideosRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->syncVideos($product, $request->validated('videos'));

        return $this->success(
            $product->videos->map(fn ($video) => [
                'id' => $video->id,
                'video_id' => $video->video_id,
                'video_url' => $video->video_url,
                'embed_url' => $video->embedUrl(),
                'thumbnail_url' => $video->thumbnailUrl(),
                'watch_url' => $video->watchUrl(),
                'title' => $video->title,
                'description' => $video->description,
                'sort_order' => $video->sort_order,
                'is_primary' => $video->is_primary,
            ]),
            'Product videos synced successfully.',
        );
    }

    private function ensureFaqBelongsToProduct(Product $product, ProductFaq $faq): void
    {
        if ($faq->product_id !== $product->id) {
            throw new NotFoundHttpException('Product FAQ not found.');
        }
    }

    private function ensureDocumentBelongsToProduct(Product $product, ProductDocument $document): void
    {
        if ($document->product_id !== $product->id) {
            throw new NotFoundHttpException('Product document not found.');
        }
    }

    private function ensureReviewBelongsToProduct(Product $product, ProductReview $review): void
    {
        if ($review->product_id !== $product->id) {
            throw new NotFoundHttpException('Product review not found.');
        }
    }

    private function ensureQuestionBelongsToProduct(Product $product, ProductQuestion $question): void
    {
        if ($question->product_id !== $product->id) {
            throw new NotFoundHttpException('Product question not found.');
        }
    }
}
