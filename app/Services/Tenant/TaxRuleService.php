<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TaxConfigurationUpdated;
use App\Models\Tenant\CustomerGroup;
use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRegion;
use App\Models\Tenant\TaxRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Manages tax rules within a tenant store.
 */
class TaxRuleService
{
    /**
     * @var list<string>
     */
    private const LIST_RELATIONS = ['taxRate.taxClass', 'taxRate.taxZone'];

    /**
     * Paginate tax rules.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TaxRule>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return TaxRule::query()
            ->with(self::LIST_RELATIONS)
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a tax rule by ID.
     */
    public function find(int $id): TaxRule
    {
        return TaxRule::query()
            ->with(self::LIST_RELATIONS)
            ->findOrFail($id);
    }

    /**
     * Create a new tax rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TaxRule
    {
        return DB::transaction(function () use ($data): TaxRule {
            $payload = $this->preparePayload($data);
            $rule = TaxRule::query()->create($payload);
            TaxConfigurationUpdated::dispatch('tax_rule');

            return $rule->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Update a tax rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TaxRule $rule, array $data): TaxRule
    {
        return DB::transaction(function () use ($rule, $data): TaxRule {
            $rule->update($this->preparePayload($data));
            TaxConfigurationUpdated::dispatch('tax_rule');

            return $rule->fresh(self::LIST_RELATIONS);
        });
    }

    /**
     * Delete a tax rule.
     */
    public function delete(TaxRule $rule): void
    {
        DB::transaction(function () use ($rule): void {
            $rule->delete();
            TaxConfigurationUpdated::dispatch('tax_rule');
        });
    }

    /**
     * Delete multiple tax rules by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $deleted = TaxRule::query()->whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                TaxConfigurationUpdated::dispatch('tax_rule');
            }

            return $deleted;
        });
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @return EloquentCollection<int, TaxRule>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): EloquentCollection {
        $query = TaxRule::query()
            ->with(self::LIST_RELATIONS)
            ->latest();

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get rules for an applicable model.
     *
     * @return EloquentCollection<int, TaxRule>
     */
    public function getByApplicable(Model $model): EloquentCollection
    {
        return TaxRule::query()
            ->where('applicable_type', $model::class)
            ->where('applicable_id', $model->getKey())
            ->where('is_active', true)
            ->with(['taxRate'])
            ->get();
    }

    /**
     * Toggle the active status of a tax rule.
     */
    public function toggleActive(TaxRule $rule): TaxRule
    {
        $rule->update(['is_active' => ! $rule->is_active]);
        TaxConfigurationUpdated::dispatch('tax_rule');

        return $rule->fresh(self::LIST_RELATIONS);
    }

    /**
     * Return aggregate counts for the admin dashboard.
     *
     * @return array{total: int, active: int, inactive: int, override: int}
     */
    public function statistics(): array
    {
        return [
            'total' => TaxRule::query()->count(),
            'active' => TaxRule::query()->where('is_active', true)->count(),
            'inactive' => TaxRule::query()->where('is_active', false)->count(),
            'override' => TaxRule::query()->where('rule_type', 'override')->count(),
        ];
    }

    /**
     * Create a new tax region.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRegion(array $data): TaxRegion
    {
        return DB::transaction(function () use ($data): TaxRegion {
            $region = TaxRegion::query()->create($data);
            TaxConfigurationUpdated::dispatch('tax_region');

            return $region;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data): array
    {
        $payload = $data;

        if (isset($data['applicable_type'])) {
            $payload['applicable_type'] = $this->resolveApplicableType((string) $data['applicable_type']);
        }

        return $payload;
    }

    private function resolveApplicableType(string $type): string
    {
        return match ($type) {
            'product' => Product::class,
            'customer_group' => CustomerGroup::class,
            Product::class, CustomerGroup::class => $type,
            default => throw new InvalidArgumentException("Unsupported applicable type [{$type}]."),
        };
    }
}
