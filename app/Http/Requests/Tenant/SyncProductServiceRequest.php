<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates product service configuration sync requests.
 */
class SyncProductServiceRequest extends FormRequest
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
            ['service' => ['required', 'array']],
            $this->productServiceRules(),
            ['providers' => ['present', 'array']],
            $this->productProviderRules('providers'),
        );
    }
}
