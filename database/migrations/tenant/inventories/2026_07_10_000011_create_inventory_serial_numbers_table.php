<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_serial_numbers', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse where the serial number is stored');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product with this serial number');
            $table->string('serial_number')->unique()->comment('Unique serial number of the item');
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete()->comment('Reference to the batch this serial belongs to');
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'damaged'])->default('available')->comment('Current status of the serial tracked item (available, reserved, sold, returned, damaged)');
            $table->timestamp('sold_at')->nullable()->comment('Timestamp when the item was sold');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the serial number was recorded');

            $table->index(['warehouse_id', 'product_id']);
            $table->index('serial_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serial_numbers');
    }
};
