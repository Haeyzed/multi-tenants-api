<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links variants to their option value combination.
 *
 * Replaces direct variant-to-attribute coupling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('product_option_id')
                ->constrained('product_options')
                ->cascadeOnDelete();

            $table->foreignId('product_option_value_id')
                ->constrained('product_option_values')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'product_option_id'],
                'variant_option_values_variant_option_unique'
            );

            $table->index(
                ['product_variant_id', 'product_option_value_id'],
                'variant_option_values_variant_value_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
    }
};
