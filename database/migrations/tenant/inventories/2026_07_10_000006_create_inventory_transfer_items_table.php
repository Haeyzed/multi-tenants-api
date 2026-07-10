<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('inventory_transfer_id')->constrained('inventory_transfers')->cascadeOnDelete()->comment('Reference to the parent transfer document');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product being transferred');
            $table->decimal('quantity_requested', 18, 4)->comment('Quantity requested for transfer');
            $table->decimal('quantity_sent', 18, 4)->default(0)->comment('Quantity actually sent from source');
            $table->decimal('quantity_received', 18, 4)->default(0)->comment('Quantity actually received at destination');
            $table->decimal('unit_cost', 18, 4)->nullable()->comment('Unit cost of the transferred items');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the item was added');

            $table->index('inventory_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
    }
};
