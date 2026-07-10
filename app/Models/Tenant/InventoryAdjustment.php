<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\InventoryAdjustmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $adjustment_number
 * @property int $warehouse_id
 * @property InventoryAdjustmentStatus $status
 * @property string|null $reference_number
 * @property string|null $reason
 * @property int|null $media_id
 * @property int $total_products
 * @property int $total_quantity_adjusted
 * @property int|null $created_by
 * @property Carbon|null $posted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Warehouse $warehouse
 * @property-read Media|null $media
 * @property-read TenantUser|null $creator
 * @property-read Collection<int, InventoryAdjustmentItem> $items
 */
class InventoryAdjustment extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'status',
        'reference_number',
        'reason',
        'media_id',
        'total_products',
        'total_quantity_adjusted',
        'created_by',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InventoryAdjustmentStatus::class,
            'total_products' => 'integer',
            'total_quantity_adjusted' => 'integer',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return BelongsTo<TenantUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    /**
     * @return HasMany<InventoryAdjustmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<InventoryAdjustment>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<InventoryAdjustment>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('adjustment_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', (int) $filters['warehouse_id']))
            ->when(! empty($filters['status']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
                $q->whereIn('status', $statuses);
            })
            ->when(! empty($filters['created_by']), fn (Builder $q) => $q->where('created_by', (int) $filters['created_by']))
            ->when(! empty($filters['start_date']), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['start_date']))
            ->when(! empty($filters['end_date']), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['end_date']));
    }
}
