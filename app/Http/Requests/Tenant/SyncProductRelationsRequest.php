<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product relation sync requests.
 */
class SyncProductRelationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        $productRule = [
            'integer',
            Rule::exists('products', 'id'),
        ];

        if ($productId) {
            $productRule[] = Rule::notIn([$productId]);
        }

        return [
            'related_product_ids' => ['present', 'array'],
            'related_product_ids.*' => $productRule,
            'cross_sell_product_ids' => ['present', 'array'],
            'cross_sell_product_ids.*' => $productRule,
            'up_sell_product_ids' => ['present', 'array'],
            'up_sell_product_ids.*' => $productRule,
        ];
    }
}
