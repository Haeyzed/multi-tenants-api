<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Tenant\Concerns\PreparesProductCatalogRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            [
                'schedules' => ['sometimes', 'array'],
                'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'min:0', 'max:6'],
                'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
                'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i'],
                'schedules.*.provider_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'schedules.*.is_available' => ['nullable', 'boolean'],
            ],
        );
    }
}
