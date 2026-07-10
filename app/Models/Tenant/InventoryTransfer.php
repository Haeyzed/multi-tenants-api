<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\InventoryTransferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transfer_number
 * @property Carbon $transfer_date
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property InventoryTransferStatus $status
 * @property string $shipping_cost
 * @property string $subtotal
 * @property string $grand_total
 * @property bool $email_sent
 * @property string|null $reason
 * @property int|null $media_id
 * @property int $total_products
 * @property int $total_quantity_transferred
 * @property int|null $created_by
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Warehouse $fromWarehouse
 * @property-read Warehouse $toWarehouse
 * @property-read Media|null $media
 * @property-read TenantUser|null $creator
 * @property-read Collection<int, InventoryTransferItem> $items
 */
class InventoryTransfer extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'transfer_number',
        'transfer_date',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'shipping_cost',
        'subtotal',
        'grand_total',
        'email_sent',
        'reason',
        'media_id',
        'total_products',
        'total_quantity_transferred',
        'created_by',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'status' => InventoryTransferStatus::class,
            'shipping_cost' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'email_sent' => 'boolean',
            'total_products' => 'integer',
            'total_quantity_transferred' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
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
     * @return HasMany<InventoryTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<InventoryTransfer>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<InventoryTransfer>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('transfer_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['from_warehouse_id']), fn (Builder $q) => $q->where('from_warehouse_id', (int) $filters['from_warehouse_id']))
            ->when(! empty($filters['to_warehouse_id']), fn (Builder $q) => $q->where('to_warehouse_id', (int) $filters['to_warehouse_id']))
            ->when(! empty($filters['status']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
                $q->whereIn('status', $statuses);
            })
            ->when(! empty($filters['start_date']), fn (Builder $q) => $q->whereDate('transfer_date', '>=', $filters['start_date']))
            ->when(! empty($filters['end_date']), fn (Builder $q) => $q->whereDate('transfer_date', '<=', $filters['end_date']));
    }
}
