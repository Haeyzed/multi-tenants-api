<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variant-generating product options (Color, Size, etc.).
 *
 * Distinct from catalog attributes used for specifications and filtering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('name')->comment('Option name e.g. Color, Size');
            $table->string('code', 50)->comment('Machine code e.g. color, size');
            $table->unsignedInteger('position')->default(0)->comment('Display order');

            $table->timestamps();

            $table->unique(['product_id', 'code'], 'product_options_product_code_unique');
            $table->index(['product_id', 'position'], 'product_options_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
