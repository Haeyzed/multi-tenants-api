<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer back-in-stock notification subscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_alerts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->string('email');
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'email'],
                'product_stock_alerts_variant_email_unique'
            );
            $table->index(['product_variant_id', 'is_notified'], 'product_stock_alerts_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_alerts');
    }
};
