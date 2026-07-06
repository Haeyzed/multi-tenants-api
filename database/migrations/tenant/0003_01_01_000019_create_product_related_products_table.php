<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related_products', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('related_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('relation_type', 40)->comment('related|accessory|replacement|upsell|cross_sell|frequently_bought_together');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['product_id', 'related_product_id', 'relation_type'],
                'product_related_unique'
            );
            $table->index(['product_id', 'relation_type'], 'product_related_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related_products');
    }
};
