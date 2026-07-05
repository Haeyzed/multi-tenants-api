<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ConvertUnitRequest extends FormRequest
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
            'value' => ['required', 'numeric'],
            'from_code' => ['required', 'string', 'exists:units,code'],
            'to_code' => ['required', 'string', 'exists:units,code'],
        ];
    }
}
