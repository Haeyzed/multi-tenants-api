<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Override rule for a tax rate on a specific entity.
 *
 * @property int $id
 * @property int $tax_rate_id
 * @property string $applicable_type
 * @property int $applicable_id
 * @property string $rule_type
 * @property string|null $adjustment_rate
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TaxRate $taxRate
 *
 * @method static Builder<TaxRule>|TaxRule query()
 */
class TaxRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tax_rate_id',
        'applicable_type',
        'applicable_id',
        'rule_type',
        'adjustment_rate',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<TaxRule>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TaxRule>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where('rule_type', 'like', "%{$search}%");
            })
            ->when(! empty($filters['tax_rate_id']), function (Builder $q) use ($filters): void {
                $q->where('tax_rate_id', $filters['tax_rate_id']);
            })
            ->when(! empty($filters['applicable_type']), function (Builder $q) use ($filters): void {
                $type = match ((string) $filters['applicable_type']) {
                    'product' => Product::class,
                    'customer_group' => CustomerGroup::class,
                    default => (string) $filters['applicable_type'],
                };
                $q->where('applicable_type', $type);
            })
            ->when(! empty($filters['rule_type']), function (Builder $q) use ($filters): void {
                $q->where('rule_type', $filters['rule_type']);
            })
            ->when(! empty($filters['is_active']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_active'])
                    ? $filters['is_active']
                    : explode(',', (string) $filters['is_active']);

                $booleans = [];

                if (in_array('active', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('inactive', $statuses, true)) {
                    $booleans[] = false;
                }

                if (! empty($booleans)) {
                    $q->whereIn('is_active', $booleans);
                }
            });
    }
}
