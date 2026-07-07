<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->string('price_type', 30)->comment('retail|compare_at|tier|promotional');
            $table->decimal('old_price', 12, 4);
            $table->decimal('new_price', 12, 4);

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['product_variant_id', 'created_at'], 'price_histories_variant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
