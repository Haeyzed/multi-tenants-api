<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\UnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Measurement unit for weight, length, volume, etc.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property string $type
 * @property string $conversion_factor
 * @property bool $is_base
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<Unit>|Unit query()
 */
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'type',
        'conversion_factor',
        'is_base',
        'sort_order',
    ];

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:8',
            'is_base' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Unit>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Unit>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['type']), function (Builder $q) use ($filters): void {
                $types = is_array($filters['type'])
                    ? $filters['type']
                    : explode(',', (string) $filters['type']);

                $q->whereIn('type', $types);
            })
            ->when(isset($filters['is_base']), function (Builder $q) use ($filters): void {
                $q->where('is_base', filter_var($filters['is_base'], FILTER_VALIDATE_BOOLEAN));
            });
    }
}
