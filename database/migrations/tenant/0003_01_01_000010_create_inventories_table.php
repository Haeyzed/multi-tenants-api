<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variant-level inventory — single source of truth for stock quantities.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete()
                ->comment('Inventory belongs to sellable variant');

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(0)->comment('On-hand quantity');
            $table->unsignedInteger('reserved_quantity')->default(0)->comment('Allocated to orders');
            $table->unsignedInteger('incoming_quantity')->default(0)->comment('Inbound purchase orders');
            $table->unsignedInteger('damaged_quantity')->default(0)->comment('Unsellable stock');
            $table->unsignedInteger('available_quantity')
                ->storedAs('quantity - reserved_quantity')
                ->comment('Sellable quantity');

            $table->unsignedInteger('reorder_level')->nullable()->comment('Low stock threshold');
            $table->unsignedInteger('reorder_quantity')->nullable()->comment('Suggested reorder qty');

            $table->string('location_code')->nullable()->comment('Bin/shelf location');
            $table->string('batch_number')->nullable()->comment('Lot/batch tracking');
            $table->date('expiry_date')->nullable()->comment('Expiration date');

            $table->timestamps();

            $table->unique(['product_variant_id', 'warehouse_id'], 'inventories_variant_warehouse_unique');
            $table->index(['warehouse_id', 'quantity'], 'inventories_warehouse_qty_idx');
            $table->index(['expiry_date'], 'inventories_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
