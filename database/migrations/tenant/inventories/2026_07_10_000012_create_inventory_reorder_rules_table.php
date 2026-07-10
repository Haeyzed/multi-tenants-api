<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reorder_rules', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse this rule applies to');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product this rule applies to');
            $table->decimal('minimum_quantity', 18, 4)->default(0)->comment('Minimum stock level before reordering');
            $table->decimal('reorder_quantity', 18, 4)->default(0)->comment('Quantity to order when reorder point is reached');
            $table->decimal('maximum_quantity', 18, 4)->default(0)->comment('Maximum stock level to maintain');
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('Preferred supplier for reordering this product');
            $table->unsignedInteger('lead_time_days')->default(0)->comment('Expected days from order to delivery');
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reorder_rules');
    }
};
