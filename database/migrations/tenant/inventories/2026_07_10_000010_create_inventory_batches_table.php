<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse where the batch is stored');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product in this batch');
            $table->string('batch_number')->comment('Internal batch or lot number');
            $table->string('supplier_batch_number')->nullable()->comment('Batch number assigned by the supplier');
            $table->date('manufacture_date')->nullable()->comment('Date when the batch was manufactured');
            $table->date('expiry_date')->nullable()->comment('Expiration or best-before date');
            $table->decimal('quantity', 18, 4)->default(0)->comment('Current quantity in this batch');
            $table->decimal('unit_cost', 18, 4)->nullable()->comment('Unit cost for items in this batch');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the batch was recorded');

            $table->index(['warehouse_id', 'product_id']);
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
