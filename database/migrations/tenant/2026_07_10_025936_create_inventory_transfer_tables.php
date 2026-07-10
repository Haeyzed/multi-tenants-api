<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-line inventory transfers between warehouses.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_transfers')) {
            Schema::create('inventory_transfers', function (Blueprint $table): void {
                $table->id();
                $table->string('transfer_number')->unique();
                $table->date('transfer_date');
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->string('status', 20)->default('pending');
                $table->decimal('shipping_cost', 12, 4)->default(0);
                $table->decimal('subtotal', 14, 4)->default(0);
                $table->decimal('grand_total', 14, 4)->default(0);
                $table->boolean('email_sent')->default(false);
                $table->text('reason')->nullable();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->unsignedInteger('total_products')->default(0);
                $table->unsignedInteger('total_quantity_transferred')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['from_warehouse_id', 'to_warehouse_id', 'status', 'transfer_date'],
                    'inv_xfer_wh_status_date_idx'
                );
                $table->index(['status', 'transfer_date'], 'inv_xfer_status_date_idx');
            });
        }

        if (! Schema::hasTable('inventory_transfer_items')) {
            Schema::create('inventory_transfer_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inventory_transfer_id')
                    ->constrained('inventory_transfers')
                    ->cascadeOnDelete();
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->restrictOnDelete();
                $table->foreignId('product_variant_id')
                    ->constrained('product_variants')
                    ->restrictOnDelete();
                $table->foreignId('inventory_id')
                    ->nullable()
                    ->constrained('inventories')
                    ->nullOnDelete();

                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 4)->default(0);
                $table->decimal('tax_rate', 8, 4)->default(0);
                $table->decimal('tax_amount', 12, 4)->default(0);
                $table->decimal('subtotal', 14, 4)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['inventory_transfer_id', 'sort_order'], 'xfer_items_transfer_sort_idx');
                $table->unique(
                    ['inventory_transfer_id', 'product_variant_id'],
                    'xfer_items_transfer_variant_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
    }
};
