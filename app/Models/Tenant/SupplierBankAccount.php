<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bank account details for a supplier.
 *
 * @property int $id
 * @property int $supplier_id
 * @property string $account_name
 * @property string $account_number
 * @property string $bank_name
 * @property string|null $bank_branch
 * @property string|null $swift_code
 * @property string|null $iban
 * @property string $currency
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 *
 * @method static Builder<SupplierBankAccount>|SupplierBankAccount query()
 */
class SupplierBankAccount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'account_name',
        'account_number',
        'bank_name',
        'bank_branch',
        'swift_code',
        'iban',
        'currency',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Supplier that owns this bank account.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
