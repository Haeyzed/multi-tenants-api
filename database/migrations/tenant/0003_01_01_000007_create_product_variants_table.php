<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sellable SKU entity — single source of truth for price, barcode, and inventory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('title')->comment('Variant display title');
            $table->string('sku')->unique()->comment('Stock keeping unit');
            $table->string('barcode')->nullable()->comment('UPC/EAN barcode');
            $table->string('gtin', 14)->nullable()->comment('Global trade item number');
            $table->string('mpn')->nullable()->comment('Manufacturer part number');

            $table->decimal('price', 12, 4)->comment('Selling price');
            $table->decimal('compare_at_price', 12, 4)->nullable()->comment('MSRP / was price');
            $table->decimal('cost_price', 12, 4)->nullable()->comment('Cost of goods');

            $table->decimal('weight', 12, 4)->nullable()->comment('Shipping weight');
            $table->decimal('length', 12, 4)->nullable();
            $table->decimal('width', 12, 4)->nullable();
            $table->decimal('height', 12, 4)->nullable();

            $table->foreignId('weight_unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->foreignId('dimension_unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->foreignId('image_media_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete()
                ->comment('Variant-specific image');

            $table->string('status', 20)->default('active')->comment('draft|active|inactive|archived');
            $table->string('visibility', 20)->default('visible')->comment('visible|hidden|catalog|search');
            $table->boolean('is_default')->default(false)->comment('Default variant for simple products');
            $table->unsignedInteger('position')->default(0)->comment('Sort order');

            $table->string('hs_code', 20)->nullable()->comment('Harmonized system code');
            $table->string('country_of_origin', 2)->nullable()->comment('ISO 3166-1 alpha-2');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status', 'position'], 'variants_product_status_position_idx');
            $table->index(['product_id', 'is_default'], 'variants_product_default_idx');
            $table->index(['barcode'], 'variants_barcode_idx');
            $table->index(['gtin'], 'variants_gtin_idx');
            $table->index(['mpn'], 'variants_mpn_idx');
            $table->unique(['barcode'], 'variants_barcode_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
