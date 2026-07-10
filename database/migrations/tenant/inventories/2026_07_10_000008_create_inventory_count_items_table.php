<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete()->comment('Reference to the parent count session');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('Reference to the product being counted');
            $table->decimal('expected_quantity', 18, 4)->comment('System expected quantity before count');
            $table->decimal('counted_quantity', 18, 4)->default(0)->comment('Actual quantity counted physically');
            $table->decimal('variance', 18, 4)->default(0)->comment('Difference between counted and expected quantities');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the count item was recorded');

            $table->index('inventory_count_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};
