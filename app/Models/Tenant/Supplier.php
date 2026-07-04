<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Product supplier or procurement partner.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $code
 * @property string|null $description
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $website_url
 * @property string|null $tax_id
 * @property string|null $registration_number
 * @property bool $is_active
 * @property int $products_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EloquentCollection<int, SupplierAddress> $addresses
 * @property-read EloquentCollection<int, SupplierBankAccount> $bankAccounts
 * @property-read EloquentCollection<int, SupplierContact> $contacts
 * @property-read EloquentCollection<int, Product> $products
 *
 * @method static Builder<Supplier>|Supplier query()
 */
class Supplier extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website_url',
        'tax_id',
        'registration_number',
        'is_active',
        'products_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'products_count' => 'integer',
        ];
    }

    /**
     * Addresses for this supplier.
     *
     * @return HasMany<SupplierAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class);
    }

    /**
     * Default address for this supplier.
     */
    public function primaryAddress(): ?SupplierAddress
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    /**
     * Bank accounts for this supplier.
     *
     * @return HasMany<SupplierBankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    /**
     * Default bank account for this supplier.
     */
    public function primaryBankAccount(): ?SupplierBankAccount
    {
        return $this->bankAccounts()->where('is_default', true)->first();
    }

    /**
     * Contacts for this supplier.
     *
     * @return HasMany<SupplierContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    /**
     * Primary contact for this supplier.
     */
    public function primaryContact(): ?SupplierContact
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    /**
     * Products sourced from this supplier.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
