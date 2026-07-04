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
        // PRODUCTS: Core product table (lean, focused, extensible)
        // ═══════════════════════════════════════════════════════════════
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // ── Categorization ──
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attribute_set_id')->nullable()->constrained()->nullOnDelete();

            // ── Identity ──
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('condition')->default('new');

            // ── Pricing ──
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();

            // ── Inventory ──
            $table->boolean('track_inventory')->default(true);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('allow_backorders')->default(false);
            $table->timestamp('restock_date')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();

            // ── Physical ──
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('length', 10, 3)->nullable();
            $table->decimal('width', 10, 3)->nullable();
            $table->decimal('height', 10, 3)->nullable();
            $table->foreignId('weight_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('dimension_unit_id')->nullable()->constrained('units')->nullOnDelete();

            // ── Identifiers ──
            $table->string('barcode')->nullable()->unique();
            $table->string('mpn')->nullable()->index();
            $table->string('gtin')->nullable()->index();
            $table->string('hs_code')->nullable();
            $table->string('country_of_origin', 2)->nullable();

            // ── Tax ──
            $table->foreignId('tax_class_id')->nullable()->constrained()->nullOnDelete();

            // ── Media ──
            $table->foreignId('primary_image_media_id')->nullable()->constrained('media')->nullOnDelete();

            // ── Type & Behavior ──
            $table->string('product_type', 20)->default('standard');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_returnable')->default(true);
            $table->unsignedInteger('return_period_days')->nullable();
            $table->unsignedInteger('warranty_period_months')->nullable();
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->unsignedInteger('max_order_quantity')->nullable();

            // ── Supplier ──
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_sku')->nullable();

            // ── Search & SEO ──
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->text('search_keywords')->nullable();

            // ── Publishing ──
            $table->timestamp('published_at')->nullable();
            $table->timestamp('discontinued_at')->nullable();

            // ── Audit ──
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──
            $table->index(['status', 'is_visible', 'is_featured']);
            $table->index(['status', 'published_at']);
            $table->index(['product_type', 'status']);
            $table->index(['category_id', 'status', 'is_visible']);
            $table->index(['brand_id', 'status', 'is_visible']);
            $table->index(['sku']);
            $table->index(['slug']);
            $table->index(['barcode']);
            $table->index(['supplier_id', 'supplier_sku']);
            $table->index(['tax_class_id']);
            $table->fullText(['name', 'summary', 'search_keywords']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT CATEGORIES: Many-to-many with primary flag
        // ═══════════════════════════════════════════════════════════════
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['category_id', 'product_id']);
            $table->index(['product_id', 'is_primary']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT TAGS: Many-to-many
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'tag_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT VARIANTS: SKU-level variations
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active', 'sort_order']);
            $table->index(['sku']);
            $table->index(['barcode']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT ATTRIBUTE VALUES: Link products to attribute values
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('custom_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'attribute_id']);
            $table->index(['product_id', 'attribute_value_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // VARIANT ATTRIBUTE VALUES: Link variants to attribute values
        // ═══════════════════════════════════════════════════════════════
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_id']);
            $table->index(['product_variant_id', 'attribute_value_id'], 'variant_attr_values_idx');
        });

        // ═══════════════════════════════════════════════════════════════
        // INVENTORIES: Stock tracking per product/variant/warehouse
        // ═══════════════════════════════════════════════════════════════
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('available_quantity')->virtualAs('quantity - reserved_quantity');
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('location_code')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'warehouse_id'], 'inv_unique');
            $table->index(['product_id', 'product_variant_id']);
            $table->index(['warehouse_id', 'quantity']);
        });

        // ═══════════════════════════════════════════════════════════════
        // INVENTORY MOVEMENTS: Stock change audit trail
        // ═══════════════════════════════════════════════════════════════
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT IMAGES: Gallery with alt text, captions, primary flag
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
            $table->index(['product_variant_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT VIDEOS: YouTube/Vimeo embeds
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('video_id', 50);
            $table->string('video_url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT DOWNLOADS: Digital product files
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_expiry_days')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_preview']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT BUNDLES: Bundle composition
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('included_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('included_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_optional')->default(false);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('fixed_price', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'included_product_id', 'included_variant_id'], 'bundle_unique');
            $table->index(['product_id', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SERVICES: Service-type product details
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique()->cascadeOnDelete();
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('buffer_minutes_before')->default(0);
            $table->unsignedInteger('buffer_minutes_after')->default(0);
            $table->unsignedInteger('max_participants')->nullable();
            $table->string('location_type')->default('any');
            $table->text('location_address')->nullable();
            $table->string('meeting_url')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->unsignedInteger('cancellation_hours')->default(24);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SERVICE PROVIDERS
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'provider_id']);
            $table->index(['product_id', 'is_primary']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SERVICE SCHEDULES: Availability windows
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_service_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'day_of_week', 'is_available'], 'prod_svc_schedules_idx');
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT REVIEWS: Customer reviews with moderation
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_approved', 'rating']);
            $table->index(['product_id', 'is_approved', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index('order_id');
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT RELATED PRODUCTS: Upsells, cross-sells, accessories
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_related_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('relation_type');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'relation_type'], 'rel_prod_unique');
            $table->index(['product_id', 'relation_type']);
        });

        // ═══════════════════════════════════════════════════════════════
        // COLLECTIONS: Curated product groups
        // ═══════════════════════════════════════════════════════════════
        Schema::create('collections', function (Blueprint $table) {
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
            $table->string('type')->default('manual');
            $table->json('conditions')->nullable();
            $table->string('sort_by')->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_visible', 'is_featured', 'sort_order']);
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['collection_id', 'product_id']);
            $table->index(['collection_id', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SEO: Dedicated SEO settings
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique()->cascadeOnDelete();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->foreignId('twitter_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('schema_markup')->nullable();
            $table->string('robots_meta')->default('index, follow');
            $table->json('custom_meta')->nullable();
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT PRICE TIERS: Volume/bulk pricing
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('customer_group_id')->nullable()->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'variant_id', 'customer_group_id'], 'price_tier_group_idx');
            $table->index(['product_id', 'min_quantity', 'max_quantity'], 'price_tier_qty_idx');
            $table->index(['starts_at', 'ends_at']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT PRICE HISTORY: Audit trail for price changes
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('price_type');
            $table->decimal('old_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['variant_id', 'created_at']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SPECIFICATIONS: Structured key-value specs
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('group')->default('general');
            $table->string('key');
            $table->text('value');
            $table->string('unit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'group', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT DOCUMENTS: Manuals, datasheets, certifications
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type');
            $table->string('language', 5)->default('en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'document_type', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT FAQs
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_visible', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SUBSCRIPTIONS: Subscription-type product details
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique()->cascadeOnDelete();
            $table->string('interval');
            $table->unsignedInteger('interval_count')->default(1);
            $table->unsignedInteger('trial_days')->default(0);
            $table->decimal('trial_price', 12, 2)->nullable();
            $table->unsignedInteger('billing_cycles')->nullable();
            $table->boolean('prorate')->default(true);
            $table->boolean('allow_pause')->default(true);
            $table->boolean('allow_cancel_anytime')->default(true);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT SHIPPING PROFILES: Shipping overrides per product
        // ═══════════════════════════════════════════════════════════════
        Schema::create('product_shipping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('profile_name');
            $table->decimal('additional_cost', 12, 2)->default(0);
            $table->boolean('is_free_shipping')->default(false);
            $table->unsignedInteger('processing_days')->default(1);
            $table->json('excluded_regions')->nullable();
            $table->json('included_regions')->nullable();
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shipping_profiles');
        Schema::dropIfExists('product_subscriptions');
        Schema::dropIfExists('product_faqs');
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_price_histories');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_seo');
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('product_related_products');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_service_schedules');
        Schema::dropIfExists('product_providers');
        Schema::dropIfExists('product_services');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_downloads');
        Schema::dropIfExists('product_videos');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('variant_attribute_values');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
