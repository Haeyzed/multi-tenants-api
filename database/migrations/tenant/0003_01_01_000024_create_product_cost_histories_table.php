<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_cost_histories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->decimal('old_cost', 12, 4);
            $table->decimal('new_cost', 12, 4);

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['product_variant_id', 'created_at'], 'cost_histories_variant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cost_histories');
    }
};
