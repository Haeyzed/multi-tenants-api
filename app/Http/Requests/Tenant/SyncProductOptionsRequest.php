<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product option sync requests.
 */
class SyncProductOptionsRequest extends FormRequest
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

        return [
            'options' => ['required', 'array'],
            'options.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_options', 'id')->where(
                    fn ($query) => $query->where('product_id', $productId),
                ),
            ],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.code' => ['nullable', 'string', 'max:50'],
            'options.*.position' => ['nullable', 'integer', 'min:0'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_option_values', 'id'),
            ],
            'options.*.values.*.value' => ['required', 'string', 'max:255'],
            'options.*.values.*.code' => ['nullable', 'string', 'max:50'],
            'options.*.values.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
