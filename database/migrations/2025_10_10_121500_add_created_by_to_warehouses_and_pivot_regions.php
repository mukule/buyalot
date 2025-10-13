<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add created_by to warehouses
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('supports_delivery')->constrained('users')->nullOnDelete();
            }
        });

        // Pivot for multi-region coverage
        if (!Schema::hasTable('warehouse_region')) {
            Schema::create('warehouse_region', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['warehouse_id', 'region_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_region')) {
            Schema::dropIfExists('warehouse_region');
        }
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
