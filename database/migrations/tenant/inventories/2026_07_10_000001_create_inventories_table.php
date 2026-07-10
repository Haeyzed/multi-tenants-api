<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse where the inventory is stored');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product in inventory');
            $table->decimal('quantity_on_hand', 18, 4)->default(0)->comment('Current physical stock quantity available');
            $table->decimal('quantity_reserved', 18, 4)->default(0)->comment('Quantity reserved for pending orders');
            $table->decimal('quantity_available', 18, 4)->default(0)->comment('Quantity available for new orders (on hand minus reserved)');
            $table->decimal('quantity_incoming', 18, 4)->default(0)->comment('Quantity expected to arrive from purchases or transfers');
            $table->decimal('quantity_outgoing', 18, 4)->default(0)->comment('Quantity scheduled to leave via sales or transfers');
            $table->decimal('reorder_level', 18, 4)->nullable()->comment('Stock level at which reorder should be triggered');
            $table->decimal('safety_stock', 18, 4)->nullable()->comment('Minimum buffer stock to prevent stockouts');
            $table->decimal('maximum_stock', 18, 4)->nullable()->comment('Upper limit for stock quantity in this warehouse');
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
