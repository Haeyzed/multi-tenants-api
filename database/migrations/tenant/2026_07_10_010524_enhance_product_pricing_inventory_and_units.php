<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotional pricing, branch/warehouse-specific prices, and clearer unit conversion inputs.
 *
 * Pricing model (Shopify/Magento-aligned):
 * - price: regular retail price
 * - compare_at_price: MSRP / was price for merchandising
 * - sale_price + sale window: scheduled promotional override
 * - product_price_tiers: quantity and customer-group tiers (unchanged)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('sale_price', 12, 4)
                ->nullable()
                ->after('cost_price')
                ->comment('Promotional sale price when sale window is active');

            $table->timestamp('sale_starts_at')
                ->nullable()
                ->after('sale_price')
                ->comment('When scheduled sale pricing begins');

            $table->timestamp('sale_ends_at')
                ->nullable()
                ->after('sale_starts_at')
                ->comment('When scheduled sale pricing ends');

            $table->boolean('use_warehouse_pricing')
                ->default(false)
                ->after('sale_ends_at')
                ->comment('When true, resolve selling price from variant_warehouse_prices');
        });

        Schema::create('variant_warehouse_prices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->decimal('price', 12, 4)->comment('Branch-specific selling price');

            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'warehouse_id'],
                'variant_warehouse_prices_unique'
            );
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->string('conversion_operator', 20)
                ->nullable()
                ->after('conversion_factor')
                ->comment('multiply|divide — UX helper; conversion_factor remains canonical');

            $table->decimal('conversion_value', 15, 8)
                ->nullable()
                ->after('conversion_operator')
                ->comment('Operand paired with conversion_operator for admin UI');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn(['conversion_operator', 'conversion_value']);
        });

        Schema::dropIfExists('variant_warehouse_prices');

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn([
                'sale_price',
                'sale_starts_at',
                'sale_ends_at',
                'use_warehouse_pricing',
            ]);
        });
    }
};
