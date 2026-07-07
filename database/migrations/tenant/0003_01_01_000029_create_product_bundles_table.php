<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_bundles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('Bundle product');

            $table->foreignId('included_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('included_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_optional')->default(false);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('fixed_price', 12, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['product_id', 'included_product_id', 'included_variant_id'],
                'product_bundles_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
