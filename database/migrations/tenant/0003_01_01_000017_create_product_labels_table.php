<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable product labels (New, Sale, Trending, etc.).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_labels', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->nullable()->comment('Hex color');
            $table->string('background_color', 7)->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_labels');
    }
};
