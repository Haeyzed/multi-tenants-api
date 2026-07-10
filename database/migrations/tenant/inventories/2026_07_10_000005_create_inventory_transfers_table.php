<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->string('transfer_number')->unique()->comment('Unique identifier for the transfer document');
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Warehouse sending the inventory');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Warehouse receiving the inventory');
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft')->comment('Current status of the transfer (draft, pending, in_transit, completed, cancelled)');
            $table->foreignId('transferred_by')->constrained('users')->comment('User who initiated the transfer');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who approved the transfer');
            $table->timestamp('shipped_at')->nullable()->comment('Timestamp when items were shipped');
            $table->timestamp('received_at')->nullable()->comment('Timestamp when items were received');
            $table->text('notes')->nullable()->comment('Additional notes about the transfer');
            $table->timestamps();

            $table->index('status');
            $table->index('source_warehouse_id');
            $table->index('destination_warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
