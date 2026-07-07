<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\TaxClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tax classification for products and categories.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TaxClass extends Model
{
    /** @use HasFactory<TaxClassFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected static function newFactory(): TaxClassFactory
    {
        return TaxClassFactory::new();
    }

    /**
     * @return HasMany<TaxRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param Builder<TaxClass> $query
     * @param array<string, mixed> $filters
     * @return Builder<TaxClass>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string)$filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
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
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
