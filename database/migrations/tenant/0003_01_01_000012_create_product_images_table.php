<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product and variant media gallery.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->cascadeOnDelete()
                ->comment('Variant-specific image');

            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false)->comment('Primary product image');

            $table->timestamps();

            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_idx');
            $table->index(['product_id', 'is_primary'], 'product_images_product_primary_idx');
            $table->index(['product_variant_id'], 'product_images_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
