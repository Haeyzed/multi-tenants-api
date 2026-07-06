<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_faqs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('question');
            $table->text('answer');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'is_visible', 'sort_order'], 'product_faqs_visible_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_faqs');
    }
};
