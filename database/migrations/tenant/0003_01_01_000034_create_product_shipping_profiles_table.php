<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_shipping_profiles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('profile_name');
            $table->decimal('additional_cost', 12, 4)->default(0);
            $table->boolean('is_free_shipping')->default(false);
            $table->unsignedInteger('processing_days')->default(1);
            $table->json('excluded_regions')->nullable();
            $table->json('included_regions')->nullable();

            $table->timestamps();

            $table->index(['product_id'], 'product_shipping_profiles_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shipping_profiles');
    }
};
