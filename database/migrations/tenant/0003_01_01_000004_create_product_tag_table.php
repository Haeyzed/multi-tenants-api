<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many product tagging.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tag', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->primary(['product_id', 'tag_id']);
            $table->index(['tag_id'], 'product_tag_tag_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag');
    }
};
