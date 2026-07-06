<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates product creation requests for the catalog + variant schema.
 */
class StoreProductRequest extends FormRequest
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
        return array_merge(
            $this->catalogRules(),
            $this->defaultVariantRules(),
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
            'default_variant.sku.required_without' => 'A SKU is required on default_variant when no variants array is provided.',
            'default_variant.price.required_without' => 'A price is required on default_variant when no variants array is provided.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCatalogForValidation();
    }
}
