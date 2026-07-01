<?php

declare(strict_types=1);

namespace App\Imports\Central;

use App\Models\Central\Plan;
use App\Services\Central\TenantService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TenantsImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function __construct(
        private readonly TenantService $tenantService,
        private readonly ?int $createdBy = null,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $planSlug = filled($row['plan'] ?? null)
                ? (string) $row['plan']
                : (string) config('billing.default_plan', 'starter');

            $this->tenantService->create([
                'name' => (string) $row['name'],
                'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : null,
                'email' => filled($row['email'] ?? null) ? (string) $row['email'] : null,
                'phone' => filled($row['phone'] ?? null) ? (string) $row['phone'] : null,
                'plan_id' => Plan::query()->where('slug', $planSlug)->value('id'),
                'subdomain' => filled($row['subdomain'] ?? null) ? (string) $row['subdomain'] : null,
                'created_by' => $this->createdBy,
                'owner' => [
                    'name' => (string) $row['owner_name'],
                    'email' => (string) $row['owner_email'],
                    'phone' => filled($row['owner_phone'] ?? null) ? (string) $row['owner_phone'] : null,
                ],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            '*.email' => ['nullable', 'email', 'max:255'],
            '*.phone' => ['nullable', 'string', 'max:30'],
            '*.plan' => ['nullable', 'string', 'max:100', 'exists:plans,slug'],
            '*.subdomain' => ['nullable', 'string', 'max:63', 'alpha_dash'],
            '*.owner_name' => ['required', 'string', 'max:255'],
            '*.owner_email' => ['required', 'email', 'max:255'],
            '*.owner_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
