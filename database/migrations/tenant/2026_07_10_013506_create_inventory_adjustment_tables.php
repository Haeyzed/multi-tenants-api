<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-line inventory adjustments with audit trail linkage to inventory_movements.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_adjustments')) {
            Schema::create('inventory_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->string('adjustment_number')->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->string('status', 20)->default('posted');
                $table->string('reference_number')->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->unsignedInteger('total_products')->default(0);
                $table->unsignedInteger('total_quantity_adjusted')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['warehouse_id', 'status', 'created_at'], 'inv_adj_wh_status_created_idx');
                $table->index(['status', 'posted_at'], 'inv_adj_status_posted_idx');
            });
        }

        if (! Schema::hasTable('inventory_adjustment_items')) {
            Schema::create('inventory_adjustment_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inventory_adjustment_id')
                    ->constrained('inventory_adjustments')
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

                $table->string('action', 20)->comment('addition|subtraction');
                $table->unsignedInteger('quantity');
                $table->integer('quantity_change')->comment('Signed delta applied to stock');
                $table->unsignedInteger('quantity_before')->default(0);
                $table->unsignedInteger('quantity_after')->default(0);
                $table->decimal('unit_cost', 12, 4)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['inventory_adjustment_id', 'sort_order'], 'adj_items_adjustment_sort_idx');
                $table->unique(
                    ['inventory_adjustment_id', 'product_variant_id'],
                    'adj_items_adjustment_variant_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_items');
        Schema::dropIfExists('inventory_adjustments');
    }
};
