<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_documents', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('media_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type', 50)->comment('manual|datasheet|certificate|warranty');
            $table->string('language', 5)->default('en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);

            $table->timestamps();

            $table->index(['product_id', 'document_type', 'sort_order'], 'product_documents_type_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
