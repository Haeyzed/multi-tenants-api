<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical slug records for automatic 301 redirects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_slug_histories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('slug')->comment('Previous slug value');
            $table->timestamp('redirected_at')->nullable();

            $table->timestamps();

            $table->unique(['slug'], 'product_slug_histories_slug_unique');
            $table->index(['product_id', 'created_at'], 'product_slug_histories_product_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_histories');
    }
};
