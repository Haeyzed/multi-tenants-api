<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse where stock is reserved');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the reserved product');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete()->comment('Reference to the order holding the reservation');
            $table->decimal('quantity', 18, 4)->comment('Quantity reserved for the order');
            $table->enum('status', ['reserved', 'released', 'fulfilled'])->default('reserved')->comment('Reservation status (reserved, released, fulfilled)');
            $table->timestamp('expires_at')->nullable()->comment('Timestamp when the reservation expires');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the reservation was created');

            $table->index(['warehouse_id', 'product_id']);
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
