<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Populate missing UUIDs
        DB::statement("
            UPDATE customer_addresses
            SET uuid = UUID()
            WHERE uuid IS NULL OR uuid = ''
        ");

        // 2. Add UNIQUE index safely (only if missing)
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS customer_addresses_uuid_unique
            ON customer_addresses (uuid)
        ");
    }

    public function down(): void
    {
        // No rollback — intentional
    }
};
