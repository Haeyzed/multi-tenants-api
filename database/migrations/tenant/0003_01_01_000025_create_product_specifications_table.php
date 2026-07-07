<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('group', 100)->default('general')->comment('Specification section');
            $table->string('key');
            $table->text('value');
            $table->string('unit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'group', 'sort_order'], 'product_specs_group_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};
