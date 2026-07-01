<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use App\Enums\Central\BillingProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates tenant subscription requests.
 */
class SubscribeTenantRequest extends FormRequest
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
        return [
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'provider' => ['sometimes', 'string', Rule::in(BillingProvider::values())],
            'payment_method' => ['nullable', 'string', 'max:255'],
        ];
    }
}
