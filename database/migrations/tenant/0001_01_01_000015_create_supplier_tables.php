<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // SUPPLIERS: Product vendors and procurement partners
        // ═══════════════════════════════════════════════════════════════
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('website_url')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('products_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });

        // ═══════════════════════════════════════════════════════════════
        // SUPPLIER ADDRESSES: Billing, shipping, and office locations
        // ═══════════════════════════════════════════════════════════════
        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('office');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['supplier_id', 'is_default']);
            $table->index(['supplier_id', 'type']);
        });

        // ═══════════════════════════════════════════════════════════════
        // SUPPLIER BANK ACCOUNTS: Payment details for procurement
        // ═══════════════════════════════════════════════════════════════
        Schema::create('supplier_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('account_name');
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('bank_branch')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['supplier_id', 'is_default']);
        });

        // ═══════════════════════════════════════════════════════════════
        // SUPPLIER CONTACTS: Additional people at the supplier
        // ═══════════════════════════════════════════════════════════════
        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['supplier_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('supplier_bank_accounts');
        Schema::dropIfExists('supplier_addresses');
        Schema::dropIfExists('suppliers');
    }
};
