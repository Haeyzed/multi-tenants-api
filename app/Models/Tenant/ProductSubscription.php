<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Subscription billing configuration for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $interval
 * @property int $interval_count
 * @property int $trial_days
 * @property string|null $trial_price
 * @property int|null $billing_cycles
 * @property bool $prorate
 * @property bool $allow_pause
 * @property bool $allow_cancel_anytime
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read bool $has_trial
 * @property-read bool $is_indefinite
 *
 * @method static Builder<ProductSubscription>|ProductSubscription query()
 */
class ProductSubscription extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'interval',
        'interval_count',
        'trial_days',
        'trial_price',
        'billing_cycles',
        'prorate',
        'allow_pause',
        'allow_cancel_anytime',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interval_count' => 'integer',
            'trial_days' => 'integer',
            'trial_price' => 'decimal:2',
            'billing_cycles' => 'integer',
            'prorate' => 'boolean',
            'allow_pause' => 'boolean',
            'allow_cancel_anytime' => 'boolean',
        ];
    }

    /**
     * Product this subscription configuration belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Determine if a trial period is configured.
     */
    public function getHasTrialAttribute(): bool
    {
        return $this->trial_days > 0;
    }

    /**
     * Determine if billing continues indefinitely.
     */
    public function getIsIndefiniteAttribute(): bool
    {
        return $this->billing_cycles === null;
    }
}
