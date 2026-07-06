<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core catalog product entity.
 *
 * Products are catalog records only. SKU, pricing, barcode, and inventory
 * live exclusively on product_variants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete()
                ->comment('Optional brand association');

            $table->foreignId('attribute_set_id')
                ->nullable()
                ->constrained('attribute_sets')
                ->nullOnDelete()
                ->comment('Specification attribute set');

            $table->foreignId('tax_class_id')
                ->nullable()
                ->constrained('tax_classes')
                ->nullOnDelete()
                ->comment('Default tax classification');

            $table->string('name')->comment('Display name');
            $table->string('slug')->unique()->comment('URL-safe identifier');
            $table->string('subtitle')->nullable()->comment('Short tagline');
            $table->text('description')->nullable()->comment('Full product description');
            $table->string('summary', 500)->nullable()->comment('Brief summary for listings');

            $table->string('type', 30)->default('simple')->comment('simple|variable|bundle|digital|service|subscription|gift_card|configurable');
            $table->string('condition', 20)->default('new')->comment('new|refurbished|used|open_box|damaged');
            $table->string('status', 20)->default('draft')->comment('draft|active|archived');
            $table->string('visibility', 20)->default('visible')->comment('visible|hidden|catalog|search');

            $table->boolean('is_featured')->default(false)->comment('Featured on storefront');
            $table->boolean('is_returnable')->default(true)->comment('Eligible for returns');
            $table->unsignedInteger('return_period_days')->nullable()->comment('Return window in days');
            $table->unsignedInteger('warranty_period_months')->nullable()->comment('Warranty duration');

            $table->unsignedInteger('min_order_quantity')->default(1)->comment('Minimum purchase quantity');
            $table->unsignedInteger('max_order_quantity')->nullable()->comment('Maximum purchase quantity');

            $table->boolean('track_inventory')->default(true)->comment('Whether variants track stock');
            $table->boolean('allow_backorders')->default(false)->comment('Sell when out of stock');
            $table->boolean('requires_shipping')->default(true)->comment('Physical fulfillment required');
            $table->boolean('is_taxable')->default(true)->comment('Subject to tax rules');

            $table->string('meta_title')->nullable()->comment('Basic SEO title');
            $table->text('meta_description')->nullable()->comment('Basic SEO description');
            $table->string('meta_keywords')->nullable()->comment('Basic SEO keywords');
            $table->text('search_keywords')->nullable()->comment('Internal search boost terms');

            $table->timestamp('published_at')->nullable()->comment('Publication datetime');
            $table->timestamp('discontinued_at')->nullable()->comment('Discontinuation datetime');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'is_featured'], 'products_status_visibility_featured_idx');
            $table->index(['type', 'status'], 'products_type_status_idx');
            $table->index(['brand_id', 'status', 'visibility'], 'products_brand_status_visibility_idx');
            $table->index(['published_at'], 'products_published_at_idx');
            $table->index(['tax_class_id'], 'products_tax_class_idx');
            $table->fullText(['name', 'summary', 'search_keywords'], 'products_search_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
