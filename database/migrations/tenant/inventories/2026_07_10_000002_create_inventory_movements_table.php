<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse where the movement occurred');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product being moved');
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete()->comment('Reference to the inventory record affected');
            $table->enum('movement_type', [
                'purchase', 'sale', 'return', 'adjustment', 'transfer_in',
                'transfer_out', 'reservation', 'reservation_release',
                'production', 'damage', 'expired', 'lost'
            ])->comment('Type of inventory movement (purchase, sale, return, etc.)');
            $table->string('reference_type')->comment('Polymorphic reference type for the source document');
            $table->unsignedBigInteger('reference_id')->comment('Polymorphic reference ID for the source document');
            $table->decimal('quantity', 18, 4)->comment('Quantity moved in this transaction');
            $table->decimal('quantity_before', 18, 4)->comment('Stock quantity before this movement');
            $table->decimal('quantity_after', 18, 4)->comment('Stock quantity after this movement');
            $table->decimal('unit_cost', 18, 4)->nullable()->comment('Unit cost at the time of movement');
            $table->text('remarks')->nullable()->comment('Additional notes or comments about the movement');
            $table->foreignId('performed_by')->constrained('users')->comment('User who performed this movement');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the movement was recorded');

            $table->index(['warehouse_id', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
