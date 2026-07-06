<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-submitted product questions with admin answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();

            $table->text('question');
            $table->text('answer')->nullable();

            $table->boolean('is_visible')->default(false);
            $table->boolean('is_answered')->default(false);
            $table->unsignedInteger('helpful_count')->default(0);

            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_visible', 'is_answered'], 'product_questions_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
    }
};
