<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use App\Models\Tenant\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates product update requests for the catalog + variant schema.
 */
class UpdateProductRequest extends FormRequest
{
    use PreparesProductCatalogRequest;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : (int) $product;
        $defaultVariantId = $product instanceof Product
            ? $product->defaultVariant()->value('id')
            : null;

        return array_merge(
            $this->catalogRules(isUpdate: true, productId: $productId ?: null),
            $this->defaultVariantRules(isUpdate: true, defaultVariantId: $defaultVariantId),
            $this->variantArrayRules(),
            $this->productOptionsRules(),
            $this->productSuppliersRules(),
            $this->productTypeSpecificRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'videos.*.video_url.regex' => 'The video URL must be a valid YouTube URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCatalogForValidation();
    }
}
