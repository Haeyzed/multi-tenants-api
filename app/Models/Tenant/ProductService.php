<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Service-type product details.
 *
 * @property int $id
 * @property int $product_id
 * @property int $duration_minutes
 * @property int $buffer_minutes_before
 * @property int $buffer_minutes_after
 * @property int|null $max_participants
 * @property string $location_type
 * @property string|null $location_address
 * @property string|null $meeting_url
 * @property bool $requires_confirmation
 * @property int $cancellation_hours
 * @property string|null $instructions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read EloquentCollection<int, ProductProvider> $providers
 * @property-read EloquentCollection<int, ProductServiceSchedule> $schedules
 * @property-read int $total_duration_minutes
 * @property-read bool $is_virtual
 *
 * @method static Builder<ProductService>|ProductService query()
 */
class ProductService extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'duration_minutes',
        'buffer_minutes_before',
        'buffer_minutes_after',
        'max_participants',
        'location_type',
        'location_address',
        'meeting_url',
        'requires_confirmation',
        'cancellation_hours',
        'instructions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'buffer_minutes_before' => 'integer',
            'buffer_minutes_after' => 'integer',
            'max_participants' => 'integer',
            'requires_confirmation' => 'boolean',
            'cancellation_hours' => 'integer',
        ];
    }

    /**
     * Product this service configuration belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Providers assigned to this service.
     *
     * @return HasMany<ProductProvider, $this>
     */
    public function providers(): HasMany
    {
        return $this->hasMany(ProductProvider::class, 'product_id', 'product_id');
    }

    /**
     * Primary provider for this service.
     */
    public function primaryProvider(): ?ProductProvider
    {
        return $this->providers()->where('is_primary', true)->first();
    }

    /**
     * Availability schedules for this service.
     *
     * @return HasMany<ProductServiceSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ProductServiceSchedule::class, 'product_id', 'product_id');
    }

    /**
     * Total duration including buffers.
     */
    public function getTotalDurationMinutesAttribute(): int
    {
        return $this->duration_minutes + $this->buffer_minutes_before + $this->buffer_minutes_after;
    }

    /**
     * Determine if the service is virtual.
     */
    public function getIsVirtualAttribute(): bool
    {
        return $this->location_type === 'virtual';
    }
}
