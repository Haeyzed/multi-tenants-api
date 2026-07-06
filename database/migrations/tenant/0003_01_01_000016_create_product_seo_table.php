<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended SEO metadata per product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_seo', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->unique();

            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_card', 30)->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->foreignId('twitter_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('schema_markup')->nullable()->comment('JSON-LD structured data');
            $table->string('robots_meta', 100)->default('index, follow');
            $table->json('custom_meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_seo');
    }
};
