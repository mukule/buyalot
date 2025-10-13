<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure table exists before altering
        if (!Schema::hasTable('warehouse_inventory_movements')) {
            return;
        }

        // Update ENUM to include 'publish'
        DB::statement(
            "ALTER TABLE `warehouse_inventory_movements` " .
            "MODIFY `type` ENUM('adjust_increase','adjust_decrease','damaged','transfer_out','transfer_in','receive','dispatch','publish') NOT NULL"
        );
    }

    public function down(): void
    {
        // Revert ENUM (remove 'publish') if table exists
        if (!Schema::hasTable('warehouse_inventory_movements')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `warehouse_inventory_movements` " .
            "MODIFY `type` ENUM('adjust_increase','adjust_decrease','damaged','transfer_out','transfer_in','receive','dispatch') NOT NULL"
        );
    }
};
