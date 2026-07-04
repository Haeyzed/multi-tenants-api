<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Override rule for a tax rate on a specific entity.
 *
 * @property int $id
 * @property int $tax_rate_id
 * @property string $applicable_type
 * @property int $applicable_id
 * @property string $rule_type
 * @property string|null $adjustment_rate
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TaxRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tax_rate_id',
        'applicable_type',
        'applicable_id',
        'rule_type',
        'adjustment_rate',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }
}
