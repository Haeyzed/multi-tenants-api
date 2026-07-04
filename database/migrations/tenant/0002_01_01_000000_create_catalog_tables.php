<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // CATEGORIES: Hierarchical product categorization
        // ═══════════════════════════════════════════════════════════════
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('summary')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('depth')->default(0);
            $table->string('path')->nullable()->index();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('color')->nullable();
            $table->string('icon_class')->nullable();
            $table->string('layout_template')->nullable();
            $table->unsignedInteger('products_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_visible', 'sort_order']);
            $table->index(['parent_id', 'is_visible', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // BRANDS: Product manufacturers/brands
        // ═══════════════════════════════════════════════════════════════
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('summary')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('products_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_visible', 'is_featured', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // TAGS: Flexible product labeling
        // ═══════════════════════════════════════════════════════════════
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('products_count')->default(0);
            $table->timestamps();

            $table->index(['is_visible', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // ATTRIBUTES: Product attribute definitions
        // ═══════════════════════════════════════════════════════════════
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('type')->default('select');
            $table->string('display_type')->default('dropdown');
            $table->text('description')->nullable();
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_visible_on_product')->default(true);
            $table->boolean('is_visible_on_listing')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variant')->default(false);
            $table->boolean('is_user_defined')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->json('default_value')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_filterable', 'sort_order']);
            $table->index(['is_variant', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // ATTRIBUTE VALUES: Predefined values for attributes
        // ═══════════════════════════════════════════════════════════════
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('slug');
            $table->string('color_hex')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // ATTRIBUTE SETS: Group attributes for categories
        // ═══════════════════════════════════════════════════════════════
        Schema::create('attribute_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_set_attributes', function (Blueprint $table) {
            $table->foreignId('attribute_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['attribute_set_id', 'attribute_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // CATEGORY ATTRIBUTE SETS: Link sets to categories
        // ═══════════════════════════════════════════════════════════════
        Schema::create('category_attribute_sets', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_set_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['category_id', 'attribute_set_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // UNITS: Measurement units (weight, length, volume, etc.)
        // ═══════════════════════════════════════════════════════════════
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('symbol');
            $table->string('type');
            $table->decimal('conversion_factor', 15, 8)->default(1);
            $table->boolean('is_base')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════════
        // TAX CLASSES: What type of product (standard, reduced, zero, exempt)
        // ═══════════════════════════════════════════════════════════════
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        // ═══════════════════════════════════════════════════════════════
        // TAX ZONES: Where the tax applies (country, state, city, postal)
        // ═══════════════════════════════════════════════════════════════
        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2)->nullable(); // ISO 3166-1 alpha-2, null = all countries
            $table->string('state')->nullable(); // State/province, null = all states
            $table->string('city')->nullable(); // City, null = all cities
            $table->string('postal_code')->nullable(); // Specific postal code, null = all
            $table->string('postal_code_pattern')->nullable(); // Regex pattern for postal codes (e.g., "SW1*")
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('radius_km', 8, 2)->nullable(); // For geo-fenced tax zones
            $table->boolean('is_default')->default(false); // Fallback zone when no match
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'country_code', 'state']);
            $table->index(['is_active', 'is_default']);
        });

        // ═══════════════════════════════════════════════════════════════
        // TAX RATES: The actual rate for a class + zone combination
        // ═══════════════════════════════════════════════════════════════
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 7, 4); // Supports rates like 19.5%, 0.0725 (US sales tax)
            $table->unsignedTinyInteger('priority')->default(1); // 1 = first, higher = stacked
            $table->boolean('is_compound')->default(false); // Compound on top of previous priorities
            $table->boolean('applies_to_shipping')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tax_class_id', 'tax_zone_id', 'priority'], 'tax_rate_unique');
            $table->index(['tax_class_id', 'tax_zone_id', 'is_active'], 'tax_rates_class_zone_active_idx');
            $table->index(['tax_zone_id', 'is_active', 'effective_from', 'effective_to'], 'tax_rates_zone_active_dates_idx');
            $table->index(['is_active', 'effective_from', 'effective_to'], 'tax_rates_active_dates_idx');
        });

        // ═══════════════════════════════════════════════════════════════
        // TAX RULES: Override rates for specific products/customers
        // ═══════════════════════════════════════════════════════════════
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->morphs('applicable'); // product, product_variant, customer_group, etc.
            $table->string('rule_type')->default('override'); // override, exempt, reduce, increase
            $table->decimal('adjustment_rate', 7, 4)->nullable(); // +/- adjustment
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tax_rate_id', 'applicable_type', 'applicable_id', 'is_active'], 'tax_rules_rate_applicable_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_zones');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('category_attribute_sets');
        Schema::dropIfExists('attribute_set_attributes');
        Schema::dropIfExists('attribute_sets');
        Schema::dropIfExists('units');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
