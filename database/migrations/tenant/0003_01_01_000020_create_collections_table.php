<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curated and smart product collections.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('type', 20)->default('manual')->comment('manual|automated');
            $table->json('conditions')->nullable()->comment('Smart collection rules');
            $table->string('sort_by', 30)->default('manual');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_visible', 'is_featured', 'sort_order'], 'collections_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
