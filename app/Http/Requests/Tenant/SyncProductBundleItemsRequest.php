<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates product bundle item sync requests.
 */
class SyncProductBundleItemsRequest extends FormRequest
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
            ['bundle_items' => ['present', 'array']],
            $this->productBundleItemRules('bundle_items'),
        );
    }
}
