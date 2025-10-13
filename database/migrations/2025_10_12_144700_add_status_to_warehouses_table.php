<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouses')) {
            return;
        }
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'status')) {
                $table->enum('status', ['active','inactive'])->default('active')->after('longitude');
            }
        });

        // Backfill status from existing boolean `active` if present
        if (Schema::hasColumn('warehouses', 'active') && Schema::hasColumn('warehouses', 'status')) {
            DB::table('warehouses')->update([
                'status' => DB::raw("CASE WHEN `active` = 1 THEN 'active' ELSE 'inactive' END"),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('warehouses')) {
            return;
        }
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
