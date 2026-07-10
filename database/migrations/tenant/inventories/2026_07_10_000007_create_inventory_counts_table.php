<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->string('count_number')->comment('Identifier for the stock count session');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse being counted');
            $table->enum('status', ['draft', 'counting', 'completed', 'approved'])->default('draft')->comment('Current status of the count (draft, counting, completed, approved)');
            $table->foreignId('counted_by')->constrained('users')->comment('User who performed the physical count');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who approved the count results');
            $table->timestamp('counted_at')->nullable()->comment('Timestamp when the count was completed');
            $table->text('notes')->nullable()->comment('Additional notes about the count');
            $table->timestamps();

            $table->index('status');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
