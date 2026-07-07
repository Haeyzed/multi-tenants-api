<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_product_label', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_label_id')
                ->constrained('product_labels')
                ->cascadeOnDelete();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->primary(['product_id', 'product_label_id']);
            $table->index(['product_label_id'], 'product_product_label_label_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_product_label');
    }
};
