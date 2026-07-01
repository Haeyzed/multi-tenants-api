<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportResourceRequest extends FormRequest
{
    /**
     * @param  list<string>  $allowedColumns
     * @param  list<string>  $idItemRules
     * @return array<string, mixed>
     */
    public static function rules(array $allowedColumns, array $idItemRules = ['integer']): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => $idItemRules,
            'delivery' => ['sometimes', 'in:download,email'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'columns' => ['nullable', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in($allowedColumns)],
            'type' => ['sometimes', 'in:xlsx,csv'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
