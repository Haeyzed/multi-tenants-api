<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_products', function (Blueprint $table): void {
            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->primary(['collection_id', 'product_id']);
            $table->index(['collection_id', 'sort_order'], 'collection_products_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_products');
    }
};
