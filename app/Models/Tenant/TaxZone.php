<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Geographic zone where tax rates apply.
 *
 * @property int $id
 * @property string $name
 * @property string|null $country_code
 * @property string|null $state
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $postal_code_pattern
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $radius_km
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EloquentCollection<int, TaxRate> $rates
 *
 * @method static Builder<TaxZone>|TaxZone query()
 */
class TaxZone extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'country_code',
        'state',
        'city',
        'postal_code',
        'postal_code_pattern',
        'latitude',
        'longitude',
        'radius_km',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * @return HasMany<TaxRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * @param Builder<TaxZone> $query
     * @param array<string, mixed> $filters
     * @return Builder<TaxZone>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string)$filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('country_code', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['country_code']), function (Builder $q) use ($filters): void {
                $q->where('country_code', $filters['country_code']);
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
            })
            ->when(isset($filters['is_default']), function (Builder $q) use ($filters): void {
                $q->where('is_default', filter_var($filters['is_default'], FILTER_VALIDATE_BOOLEAN));
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_km' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
