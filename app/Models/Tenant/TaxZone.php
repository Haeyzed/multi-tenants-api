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

    /**
     * Tax rates configured for this zone.
     *
     * @return HasMany<TaxRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }
}
