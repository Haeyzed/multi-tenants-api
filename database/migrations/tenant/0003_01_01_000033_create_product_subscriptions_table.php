<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subscriptions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->unique();

            $table->string('interval', 20)->comment('day|week|month|year');
            $table->unsignedInteger('interval_count')->default(1);
            $table->unsignedInteger('trial_days')->default(0);
            $table->decimal('trial_price', 12, 4)->nullable();
            $table->unsignedInteger('billing_cycles')->nullable()->comment('Null = unlimited');

            $table->boolean('prorate')->default(true);
            $table->boolean('allow_pause')->default(true);
            $table->boolean('allow_cancel_anytime')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subscriptions');
    }
};
