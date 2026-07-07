<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit trail of inventory quantity changes.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->cascadeOnDelete();

            $table->integer('quantity_change')->comment('Signed delta');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');

            $table->string('type', 30)->comment('adjustment|sale|return|transfer|receipt|reservation|release|damage|shrinkage|restock|initial');

            $table->string('reference_type')->nullable()->comment('Polymorphic source type');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Polymorphic source ID');

            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['inventory_id', 'created_at'], 'inventory_movements_inventory_date_idx');
            $table->index(['reference_type', 'reference_id'], 'inventory_movements_reference_idx');
            $table->index(['type', 'created_at'], 'inventory_movements_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
