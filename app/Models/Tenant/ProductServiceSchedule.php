<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Availability window for a service product.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $provider_id
 * @property int $day_of_week
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property bool $is_available
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read TenantUser|null $provider
 * @property-read string $day_name
 *
 * @method static Builder<ProductServiceSchedule>|ProductServiceSchedule query()
 */
class ProductServiceSchedule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'provider_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_available' => 'boolean',
        ];
    }

    /**
     * Product this schedule belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Provider for this schedule window.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'provider_id');
    }

    /**
     * Human-readable day name.
     */
    public function getDayNameAttribute(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $days[$this->day_of_week] ?? 'Unknown';
    }
}
