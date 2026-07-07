<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Imports\Concerns\NormalizesImportRows;
use App\Imports\Concerns\TracksImportResults;
use App\Models\Tenant\CustomerGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerGroupsImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use NormalizesImportRows, SkipsFailures, TracksImportResults;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = (string) $row['name'];

            $attributes = [
                'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
                'discount_percent' => $this->parseDecimal($row['discount_percent'] ?? null),
                'is_active' => $this->parseBoolean($row['is_active'] ?? true),
            ];

            $existing = CustomerGroup::withTrashed()->where('name', $name)->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            CustomerGroup::query()->updateOrCreate(['name' => $name], $attributes);

            $this->incrementImported();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index): array
    {
        return $this->nullifyEmpty($data, [
            'description',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.description' => ['nullable', 'string'],
            '*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            '*.is_active' => ['nullable'],
        ];
    }
}
