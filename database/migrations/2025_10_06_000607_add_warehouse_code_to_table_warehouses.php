<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'code')) {
                $table->string('code')->unique()->after('id');
            }

            if (!Schema::hasColumn('warehouses', 'type')) {
                $table->enum('type', ['warehouse', 'store', 'pickup_point', 'dispatch_center','general'])
                    ->default('warehouse')->default('general')
                    ->after('slug');
            }

            if (!Schema::hasColumn('warehouses', 'region_id')) {
                $table->foreignId('region_id')
                    ->nullable()
                    ->after('type')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('warehouses', 'parent_warehouse_id')) {
                $table->foreignId('parent_warehouse_id')
                    ->nullable()
                    ->after('region_id')
                    ->constrained('warehouses')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('warehouses', 'address')) {
                $table->string('address')->nullable()->after('location');
            }

            if (!Schema::hasColumn('warehouses', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('active');
            }

            if (!Schema::hasColumn('warehouses', 'capacity')) {
                $table->integer('capacity')->nullable()->after('is_default');
            }

            if (!Schema::hasColumn('warehouses', 'supports_pos')) {
                $table->boolean('supports_pos')->default(false)->after('capacity');
            }

            if (!Schema::hasColumn('warehouses', 'supports_pickup')) {
                $table->boolean('supports_pickup')->default(false)->after('supports_pos');
            }

            if (!Schema::hasColumn('warehouses', 'supports_delivery')) {
                $table->boolean('supports_delivery')->default(true)->after('supports_pickup');
            }
            if (Schema::hasColumn('warehouses', 'manager_name')) {
                $table->dropColumn(['manager_name', 'manager_phone', 'email']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['parent_warehouse_id']);

            $table->dropColumn([
                'code', 'type', 'region_id', 'parent_warehouse_id',
                'address', 'is_default', 'capacity',
                'supports_pos', 'supports_pickup', 'supports_delivery'
            ]);

            // Optionally restore manager fields
            $table->string('manager_name')->nullable();
            $table->string('manager_phone')->nullable();
            $table->string('email')->nullable();
        });
    }
};
