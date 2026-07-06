<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\ProductVisibility;
use App\Enums\Tenant\VariantStatus;
use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates product variant creation requests.
 */
class StoreProductVariantRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'gtin' => ['nullable', 'string', 'max:100'],
            'mpn' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'weight_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'dimension_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'status' => ['sometimes', new Enum(VariantStatus::class)],
            'visibility' => ['sometimes', new Enum(ProductVisibility::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer', Rule::exists('product_option_values', 'id')],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'inventory' => ['nullable', 'array'],
            'inventory.warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')],
            'inventory.quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.reorder_level' => ['nullable', 'integer', 'min:0'],
            'inventory.reserved_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.incoming_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory.location_code' => ['nullable', 'string', 'max:100'],
            'inventory.batch_number' => ['nullable', 'string', 'max:100'],
            'inventory.expiry_date' => ['nullable', 'date'],
            ...$this->variantPriceTierRules('price_tiers'),
        ];
    }
}
