<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product-level specification attribute values (filtering/specs).
 *
 * Distinct from variant options used for SKU generation.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->constrained('attributes')
                ->cascadeOnDelete();

            $table->foreignId('attribute_value_id')
                ->nullable()
                ->constrained('attribute_values')
                ->cascadeOnDelete();

            $table->text('custom_value')->nullable()->comment('Free-form when no predefined value');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'attribute_id'], 'product_attr_values_product_attr_idx');
            $table->index(['product_id', 'attribute_value_id'], 'product_attr_values_product_value_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
