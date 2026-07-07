<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Concerns;

use App\Enums\Tenant\ProductCondition;
use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use App\Enums\Tenant\VariantStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait PreparesProductCatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function catalogRules(bool $isUpdate = false, ?int $productId = null): array
    {
        $sometimes = $isUpdate ? 'sometimes' : 'nullable';
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                $sometimes,
                'string',
                'max:255',
                $productId
                    ? Rule::unique('products', 'slug')->ignore($productId)
                    : Rule::unique('products', 'slug'),
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'type' => [$required, new Enum(ProductType::class)],
            'condition' => ['sometimes', new Enum(ProductCondition::class)],
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'visibility' => ['sometimes', new Enum(ProductVisibility::class)],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'attribute_set_id' => ['nullable', 'integer', Rule::exists('attribute_sets', 'id')],
            'tax_class_id' => ['nullable', 'integer', Rule::exists('tax_classes', 'id')],
            'is_featured' => ['sometimes', 'boolean'],
            'is_returnable' => ['sometimes', 'boolean'],
            'return_period_days' => ['nullable', 'integer', 'min:0'],
            'warranty_period_months' => ['nullable', 'integer', 'min:0'],
            'min_order_quantity' => ['sometimes', 'integer', 'min:1'],
            'max_order_quantity' => ['nullable', 'integer', 'min:1'],
            'track_inventory' => ['sometimes', 'boolean'],
            'allow_backorders' => ['sometimes', 'boolean'],
            'requires_shipping' => ['sometimes', 'boolean'],
            'is_taxable' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'search_keywords' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'discontinued_at' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'primary_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['integer', Rule::exists('product_labels', 'id')],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer', Rule::exists('collections', 'id')],

            'gallery' => ['nullable', 'array'],
            'gallery.*.media_id' => ['required_with:gallery', 'integer', Rule::exists('media', 'id')],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:255'],
            'gallery.*.caption' => ['nullable', 'string', 'max:500'],
            'gallery.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gallery.*.is_primary' => ['nullable', 'boolean'],
            'primary_image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],

            'videos' => ['nullable', 'array'],
            'videos.*.video_url' => ['required_with:videos', 'string', 'max:500', 'regex:/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)[a-zA-Z0-9_-]{11}.*$/'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.description' => ['nullable', 'string', 'max:1000'],
            'videos.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'videos.*.is_primary' => ['nullable', 'boolean'],

            'related_product_ids' => ['nullable', 'array'],
            'related_product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'cross_sell_product_ids' => ['nullable', 'array'],
            'cross_sell_product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'up_sell_product_ids' => ['nullable', 'array'],
            'up_sell_product_ids.*' => ['integer', Rule::exists('products', 'id')],

            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*.attribute_id' => ['required_with:attribute_values', 'integer', Rule::exists('attributes', 'id')],
            'attribute_values.*.attribute_value_id' => ['nullable', 'integer', Rule::exists('attribute_values', 'id')],
            'attribute_values.*.custom_value' => ['nullable', 'string'],

            'seo' => ['nullable', 'array'],
            'seo.canonical_url' => ['nullable', 'url', 'max:500'],
            'seo.og_title' => ['nullable', 'string', 'max:255'],
            'seo.og_description' => ['nullable', 'string'],
            'seo.og_image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'seo.twitter_card' => ['nullable', 'string', 'max:50'],
            'seo.twitter_title' => ['nullable', 'string', 'max:255'],
            'seo.twitter_description' => ['nullable', 'string'],
            'seo.twitter_image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'seo.robots_meta' => ['nullable', 'string', 'max:100'],

            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'gtin' => ['nullable', 'string', 'max:100'],
            'mpn' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultVariantRules(bool $isUpdate = false, ?int $defaultVariantId = null): array
    {
        $skuUnique = $defaultVariantId
            ? Rule::unique('product_variants', 'sku')->ignore($defaultVariantId)
            : Rule::unique('product_variants', 'sku');

        return [
            'default_variant' => ['nullable', 'array'],
            'default_variant.title' => ['nullable', 'string', 'max:255'],
            'default_variant.sku' => [
                $isUpdate ? 'sometimes' : 'required_without:variants',
                'nullable',
                'string',
                'max:100',
                $skuUnique,
            ],
            'default_variant.price' => [$isUpdate ? 'sometimes' : 'required_without:variants', 'nullable', 'numeric', 'min:0'],
            'default_variant.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'default_variant.cost_price' => ['nullable', 'numeric', 'min:0'],
            'default_variant.barcode' => ['nullable', 'string', 'max:100'],
            'default_variant.gtin' => ['nullable', 'string', 'max:100'],
            'default_variant.mpn' => ['nullable', 'string', 'max:100'],
            'default_variant.weight' => ['nullable', 'numeric', 'min:0'],
            'default_variant.length' => ['nullable', 'numeric', 'min:0'],
            'default_variant.width' => ['nullable', 'numeric', 'min:0'],
            'default_variant.height' => ['nullable', 'numeric', 'min:0'],
            'default_variant.weight_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'default_variant.dimension_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'default_variant.inventory' => ['nullable', 'array'],
            'default_variant.inventory.warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')],
            'default_variant.inventory.quantity' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.reorder_level' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.reserved_quantity' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.incoming_quantity' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'default_variant.inventory.location_code' => ['nullable', 'string', 'max:100'],
            'default_variant.inventory.batch_number' => ['nullable', 'string', 'max:100'],
            'default_variant.inventory.expiry_date' => ['nullable', 'date'],
            ...$this->variantPriceTierRules('default_variant.price_tiers'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function variantPriceTierRules(string $prefix): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.*.min_quantity" => ['required_with:'.$prefix, 'integer', 'min:1'],
            "{$prefix}.*.max_quantity" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.*.price" => ['required_with:'.$prefix, 'numeric', 'min:0'],
            "{$prefix}.*.customer_group_id" => ['nullable', 'integer', Rule::exists('customer_groups', 'id')],
            "{$prefix}.*.starts_at" => ['nullable', 'date'],
            "{$prefix}.*.ends_at" => ['nullable', 'date', 'after_or_equal:'.$prefix.'.*.starts_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function variantArrayRules(): array
    {
        return [
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.title' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.gtin' => ['nullable', 'string', 'max:100'],
            'variants.*.mpn' => ['nullable', 'string', 'max:100'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.length' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0'],
            'variants.*.weight_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'variants.*.dimension_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'variants.*.status' => ['nullable', new Enum(VariantStatus::class)],
            'variants.*.visibility' => ['nullable', new Enum(ProductVisibility::class)],
            'variants.*.position' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.option_value_ids' => ['nullable', 'array'],
            'variants.*.option_value_ids.*' => ['integer', Rule::exists('product_option_values', 'id')],
            'variants.*.inventory' => ['nullable', 'array'],
            'variants.*.inventory.warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')],
            'variants.*.inventory.quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.reorder_level' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.reserved_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.incoming_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.location_code' => ['nullable', 'string', 'max:100'],
            'variants.*.inventory.batch_number' => ['nullable', 'string', 'max:100'],
            'variants.*.inventory.expiry_date' => ['nullable', 'date'],
            ...$this->variantPriceTierRules('variants.*.price_tiers'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productOptionsRules(): array
    {
        return [
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer', Rule::exists('product_options', 'id')],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.code' => ['nullable', 'string', 'max:50'],
            'options.*.position' => ['nullable', 'integer', 'min:0'],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
            'options.*.values.*.id' => ['nullable', 'integer', Rule::exists('product_option_values', 'id')],
            'options.*.values.*.value' => ['required_with:options.*.values', 'string', 'max:255'],
            'options.*.values.*.code' => ['nullable', 'string', 'max:50'],
            'options.*.values.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productSuppliersRules(): array
    {
        return [
            'suppliers' => ['nullable', 'array'],
            'suppliers.*.supplier_id' => ['required_with:suppliers', 'integer', Rule::exists('suppliers', 'id')],
            'suppliers.*.supplier_sku' => ['nullable', 'string', 'max:100'],
            'suppliers.*.supplier_cost' => ['nullable', 'numeric', 'min:0'],
            'suppliers.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'suppliers.*.minimum_quantity' => ['nullable', 'integer', 'min:1'],
            'suppliers.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productTypeSpecificRules(): array
    {
        return array_merge(
            $this->productDownloadRules('downloads'),
            $this->productBundleItemRules('bundle_items'),
            $this->productServiceRules(),
            $this->productSubscriptionRules(),
            $this->productProviderRules('providers'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function productDownloadRules(string $prefix = 'downloads'): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.*.media_id" => ['required_with:'.$prefix, 'integer', Rule::exists('media', 'id')],
            "{$prefix}.*.file_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.*.display_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.*.description" => ['nullable', 'string'],
            "{$prefix}.*.download_limit" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.*.download_expiry_days" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.*.sort_order" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.*.is_preview" => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productBundleItemRules(string $prefix = 'bundle_items'): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.*.included_product_id" => ['required_with:'.$prefix, 'integer', Rule::exists('products', 'id')],
            "{$prefix}.*.included_variant_id" => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            "{$prefix}.*.quantity" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.*.is_optional" => ['nullable', 'boolean'],
            "{$prefix}.*.discount_percentage" => ['nullable', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.*.fixed_price" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.*.sort_order" => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productServiceRules(): array
    {
        return [
            'service' => ['nullable', 'array'],
            'service.duration_minutes' => ['required_with:service', 'integer', 'min:1'],
            'service.buffer_minutes_before' => ['nullable', 'integer', 'min:0'],
            'service.buffer_minutes_after' => ['nullable', 'integer', 'min:0'],
            'service.max_participants' => ['nullable', 'integer', 'min:1'],
            'service.location_type' => ['nullable', 'string', Rule::in(['any', 'in_person', 'online', 'hybrid'])],
            'service.location_address' => ['nullable', 'string'],
            'service.meeting_url' => ['nullable', 'url', 'max:500'],
            'service.requires_confirmation' => ['nullable', 'boolean'],
            'service.cancellation_hours' => ['nullable', 'integer', 'min:0'],
            'service.instructions' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productSubscriptionRules(): array
    {
        return [
            'subscription' => ['nullable', 'array'],
            'subscription.interval' => ['required_with:subscription', 'string', Rule::in(['day', 'week', 'month', 'year'])],
            'subscription.interval_count' => ['nullable', 'integer', 'min:1'],
            'subscription.trial_days' => ['nullable', 'integer', 'min:0'],
            'subscription.trial_price' => ['nullable', 'numeric', 'min:0'],
            'subscription.billing_cycles' => ['nullable', 'integer', 'min:1'],
            'subscription.prorate' => ['nullable', 'boolean'],
            'subscription.allow_pause' => ['nullable', 'boolean'],
            'subscription.allow_cancel_anytime' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productProviderRules(string $prefix = 'providers'): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.*.provider_id" => ['required_with:'.$prefix, 'integer', Rule::exists('users', 'id')],
            "{$prefix}.*.is_primary" => ['nullable', 'boolean'],
            "{$prefix}.*.commission_rate" => ['nullable', 'numeric', 'min:0', 'max:100'],
            'provider_ids' => ['nullable', 'array'],
            'provider_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }

    protected function prepareCatalogForValidation(): void
    {
        $merge = [];

        if ($this->has('product_type') && ! $this->has('type')) {
            $legacyType = (string) $this->input('product_type');
            $merge['type'] = match ($legacyType) {
                'standard' => ProductType::Simple->value,
                'combo' => ProductType::Bundle->value,
                default => $legacyType,
            };
        }

        if ($this->has('short_description') && ! $this->has('summary')) {
            $merge['summary'] = $this->input('short_description');
        }

        if ($this->has('is_visible') && ! $this->has('visibility')) {
            $merge['visibility'] = filter_var($this->input('is_visible'), FILTER_VALIDATE_BOOLEAN)
                ? ProductVisibility::Visible->value
                : ProductVisibility::Hidden->value;
        }

        if ($this->has('taxable') && ! $this->has('is_taxable')) {
            $merge['is_taxable'] = $this->input('taxable');
        }

        $defaultVariant = $this->input('default_variant', []);

        if (! is_array($defaultVariant)) {
            $defaultVariant = [];
        }

        foreach (['sku', 'price', 'compare_at_price', 'cost_price', 'barcode', 'gtin', 'mpn'] as $field) {
            if ($this->has($field) && ! array_key_exists($field, $defaultVariant)) {
                $defaultVariant[$field] = $this->input($field);
            }
        }

        if ($defaultVariant !== []) {
            $merge['default_variant'] = $defaultVariant;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        if ($this->has('videos')) {
            $videos = $this->input('videos', []);

            foreach ($videos as $key => $video) {
                if (! empty($video['video_url'])) {
                    $videos[$key]['video_id'] = $this->extractYouTubeId($video['video_url']);
                }
            }

            $this->merge(['videos' => $videos]);
        }
    }

    private function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
