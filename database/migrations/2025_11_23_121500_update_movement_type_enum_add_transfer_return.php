<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_inventory_movements')) {
            return;
        }

        // Extend ENUM to include 'transfer_return'
        // Keep all existing values found in earlier migrations and add the new one.
        DB::statement(
            "ALTER TABLE `warehouse_inventory_movements` " .
            "MODIFY `type` ENUM('adjust_increase','adjust_decrease','damaged','transfer_out','transfer_in','receive','dispatch','publish','transfer_return') NOT NULL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('warehouse_inventory_movements')) {
            return;
        }

        // Revert: remove 'transfer_return' from ENUM
        DB::statement(
            "ALTER TABLE `warehouse_inventory_movements` " .
            "MODIFY `type` ENUM('adjust_increase','adjust_decrease','damaged','transfer_out','transfer_in','receive','dispatch','publish') NOT NULL"
        );
    }
};
