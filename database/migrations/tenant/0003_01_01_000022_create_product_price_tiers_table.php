<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiered and scheduled pricing per variant.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('customer_group_id')
                ->nullable()
                ->constrained('customer_groups')
                ->nullOnDelete()
                ->comment('Customer group specific pricing');

            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('price', 12, 4);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(
                ['product_variant_id', 'customer_group_id', 'min_quantity'],
                'price_tiers_variant_group_qty_idx'
            );
            $table->index(['starts_at', 'ends_at'], 'price_tiers_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
