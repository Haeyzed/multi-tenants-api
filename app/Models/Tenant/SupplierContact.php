<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Contact person for a supplier.
 *
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $position
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 *
 * @method static Builder<SupplierContact>|SupplierContact query()
 */
class SupplierContact extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'name',
        'email',
        'phone',
        'position',
        'is_primary',
    ];

    /**
     * Supplier that owns this contact.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
