<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id()->comment('Primary key identifier');
            $table->string('adjustment_number')->unique()->comment('Unique identifier for the adjustment document');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Reference to the warehouse being adjusted');
            $table->enum('adjustment_type', ['increase', 'decrease'])->comment('Direction of adjustment (increase or decrease)');
            $table->string('reason')->comment('Reason or justification for the adjustment');
            $table->foreignId('attachment_media_id')->nullable()->constrained('media')->nullOnDelete()->comment('Reference to attached media or documents');
            $table->text('notes')->nullable()->comment('Additional notes about the adjustment');
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft')->comment('Current status of the adjustment (draft, approved, cancelled)');
            $table->foreignId('adjusted_by')->constrained('users')->comment('User who created the adjustment');
            $table->timestamp('adjusted_at')->nullable()->comment('Timestamp when the adjustment was applied');
            $table->timestamps();

            $table->index('status');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
