<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_providers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->decimal('commission_rate', 5, 2)->nullable();

            $table->timestamps();

            $table->unique(['product_id', 'provider_id'], 'product_providers_unique');
            $table->index(['product_id', 'is_primary'], 'product_providers_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_providers');
    }
};
