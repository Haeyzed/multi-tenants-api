<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->unsignedBigInteger('order_id')->nullable();

            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();

            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('quality_rating')->nullable();
            $table->unsignedTinyInteger('delivery_rating')->nullable();
            $table->unsignedTinyInteger('value_rating')->nullable();

            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->json('images')->nullable();

            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('would_recommend')->nullable();

            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_reviews')
                ->nullOnDelete()
                ->comment('Reply threading');

            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_approved', 'rating'], 'product_reviews_product_rating_idx');
            $table->index(['product_id', 'is_approved', 'created_at'], 'product_reviews_product_date_idx');
            $table->index(['customer_id', 'created_at'], 'product_reviews_customer_date_idx');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
