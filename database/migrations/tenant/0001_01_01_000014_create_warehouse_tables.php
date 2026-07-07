<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // WAREHOUSES: Physical storage locations
        // ═══════════════════════════════════════════════════════════════
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('is_primary');
        });

        // ═══════════════════════════════════════════════════════════════
        // WAREHOUSE ZONES: Logical areas within a warehouse
        // ═══════════════════════════════════════════════════════════════
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('zone_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'is_active', 'sort_order']);
        });

        // ═══════════════════════════════════════════════════════════════
        // WAREHOUSE LOCATIONS: Bin/shelf-level storage positions
        // ═══════════════════════════════════════════════════════════════
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->nullOnDelete();
            $table->string('code');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('max_weight', 12, 3)->nullable();
            $table->decimal('max_volume', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_picking_location')->default(false);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'zone_id', 'is_active']);
            $table->index(['warehouse_id', 'is_picking_location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
    }
};
