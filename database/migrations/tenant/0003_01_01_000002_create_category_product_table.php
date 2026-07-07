<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many product categorization with primary category support.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->boolean('is_primary')->default(false)->comment('Primary navigation category');
            $table->unsignedInteger('sort_order')->default(0)->comment('Position within category');

            $table->timestamps();

            $table->primary(['category_id', 'product_id']);
            $table->index(['product_id', 'is_primary'], 'category_product_primary_idx');
            $table->index(['category_id', 'sort_order'], 'category_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
