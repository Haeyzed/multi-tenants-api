<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Models\Tenant\TaxRate;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TaxRatesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): TaxRate
    {
        return new TaxRate([
            'name' => (string) $row['name'],
            'tax_class_id' => (int) $row['tax_class_id'],
            'tax_zone_id' => (int) $row['tax_zone_id'],
            'rate' => (float) $row['rate'],
            'priority' => filled($row['priority'] ?? null) ? (int) $row['priority'] : 1,
            'is_compound' => filter_var($row['is_compound'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'applies_to_shipping' => filter_var($row['applies_to_shipping'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'effective_from' => $row['effective_from'] ?? null,
            'effective_to' => $row['effective_to'] ?? null,
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.tax_class_id' => ['required', 'integer', 'exists:tax_classes,id'],
            '*.tax_zone_id' => ['required', 'integer', 'exists:tax_zones,id'],
            '*.rate' => ['required', 'numeric', 'min:0', 'max:100'],
            '*.priority' => ['nullable', 'integer', 'min:1'],
            '*.is_compound' => ['nullable'],
            '*.applies_to_shipping' => ['nullable'],
            '*.effective_from' => ['nullable', 'date'],
            '*.effective_to' => ['nullable', 'date'],
            '*.is_active' => ['nullable'],
        ];
    }
}
