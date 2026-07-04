<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
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
}
