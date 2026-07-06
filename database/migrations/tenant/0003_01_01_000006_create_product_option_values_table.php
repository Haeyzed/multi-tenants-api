<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Values for product options used to generate variants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_option_id')
                ->constrained('product_options')
                ->cascadeOnDelete();

            $table->string('value')->comment('Display value e.g. Red, XL');
            $table->string('code', 50)->comment('Machine value e.g. red, xl');
            $table->unsignedInteger('position')->default(0)->comment('Display order');

            $table->timestamps();

            $table->unique(['product_option_id', 'code'], 'product_option_values_option_code_unique');
            $table->index(['product_option_id', 'position'], 'product_option_values_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
