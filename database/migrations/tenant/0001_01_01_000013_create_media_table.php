<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // MEDIA LIBRARY: Hierarchical folder structure
        // ─────────────────────────────────────────────
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('path')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });

        // ─────────────────────────────────────────────
        // MEDIA: Polymorphic media storage (Spatie-like)
        // ─────────────────────────────────────────────
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name')->default('default');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk')->default('public');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations')->nullable();
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images')->nullable();
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_folders');
    }
};
