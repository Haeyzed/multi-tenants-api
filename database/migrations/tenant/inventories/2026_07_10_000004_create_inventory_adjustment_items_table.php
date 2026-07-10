<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('inventory_adjustment_id')->constrained('inventory_adjustments')->cascadeOnDelete()->comment('Reference to the parent adjustment document');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product being adjusted');
            $table->decimal('quantity_before', 18, 4)->comment('Stock quantity before adjustment');
            $table->decimal('quantity_adjusted', 18, 4)->comment('Quantity added or removed');
            $table->decimal('quantity_after', 18, 4)->comment('Stock quantity after adjustment');
            $table->decimal('unit_cost', 18, 4)->nullable()->comment('Unit cost of the adjusted items');
            $table->string('reason')->nullable()->comment('Specific reason for this line item adjustment');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the item was added');

            $table->index('inventory_adjustment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_items');
    }
};
