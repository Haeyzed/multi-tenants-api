<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_services', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->unique();

            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('buffer_minutes_before')->default(0);
            $table->unsignedInteger('buffer_minutes_after')->default(0);
            $table->unsignedInteger('max_participants')->nullable();

            $table->string('location_type', 30)->default('any')->comment('any|in_person|online|hybrid');
            $table->text('location_address')->nullable();
            $table->string('meeting_url')->nullable();

            $table->boolean('requires_confirmation')->default(false);
            $table->unsignedInteger('cancellation_hours')->default(24);
            $table->text('instructions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_services');
    }
};
