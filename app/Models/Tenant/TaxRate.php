<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tax rate within a tax class and zone.
 *
 * @property int $id
 * @property int $tax_class_id
 * @property int $tax_zone_id
 * @property string $name
 * @property string $rate
 * @property int $priority
 * @property bool $is_compound
 * @property bool $applies_to_shipping
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TaxClass $taxClass
 * @property-read TaxZone $taxZone
 *
 * @method static Builder<TaxRate>|TaxRate query()
 */
class TaxRate extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tax_class_id',
        'tax_zone_id',
        'name',
        'rate',
        'priority',
        'is_compound',
        'applies_to_shipping',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /**
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    /**
     * @return BelongsTo<TaxZone, $this>
     */
    public function taxZone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class);
    }

    /**
     * @return HasMany<TaxRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    /**
     * Scope a query to currently active rates.
     *
     * @param Builder<TaxRate> $query
     * @return Builder<TaxRate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            });
    }

    /**
     * @param Builder<TaxRate> $query
     * @param array<string, mixed> $filters
     * @return Builder<TaxRate>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string)$filters['search'];
                $q->where('name', 'like', "%{$search}%");
            })
            ->when(!empty($filters['tax_class_id']), function (Builder $q) use ($filters): void {
                $q->where('tax_class_id', $filters['tax_class_id']);
            })
            ->when(!empty($filters['tax_zone_id']), function (Builder $q) use ($filters): void {
                $q->where('tax_zone_id', $filters['tax_zone_id']);
            })
            ->when(!empty($filters['is_active']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_active'])
                    ? $filters['is_active']
                    : explode(',', (string)$filters['is_active']);

                $booleans = [];

                if (in_array('active', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('inactive', $statuses, true)) {
                    $booleans[] = false;
                }

                if (!empty($booleans)) {
                    $q->whereIn('is_active', $booleans);
                }
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'priority' => 'integer',
            'is_compound' => 'boolean',
            'applies_to_shipping' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }
}
