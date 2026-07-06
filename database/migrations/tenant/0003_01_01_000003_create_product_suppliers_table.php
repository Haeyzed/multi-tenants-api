<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many product supplier sourcing with commercial terms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->string('supplier_sku')->nullable()->comment('Supplier catalog SKU');
            $table->decimal('supplier_cost', 12, 4)->nullable()->comment('Supplier unit cost');
            $table->unsignedInteger('lead_time_days')->nullable()->comment('Procurement lead time');
            $table->unsignedInteger('minimum_quantity')->default(1)->comment('Minimum order quantity');
            $table->boolean('is_primary')->default(false)->comment('Default supplier for procurement');

            $table->timestamps();

            $table->unique(['product_id', 'supplier_id'], 'product_suppliers_unique');
            $table->index(['supplier_id', 'supplier_sku'], 'product_suppliers_supplier_sku_idx');
            $table->index(['product_id', 'is_primary'], 'product_suppliers_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
