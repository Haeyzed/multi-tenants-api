<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_downloads', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();

            $table->string('file_name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_expiry_days')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_preview')->default(false);

            $table->timestamps();

            $table->index(['product_id', 'sort_order'], 'product_downloads_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_downloads');
    }
};
