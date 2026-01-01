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

        // 2. Check if index exists
        $indexExists = DB::selectOne("
            SELECT COUNT(1) AS count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'customer_addresses'
              AND index_name = 'customer_addresses_uuid_unique'
        ");

        if ($indexExists->count == 0) {
            DB::statement("
                CREATE UNIQUE INDEX customer_addresses_uuid_unique
                ON customer_addresses (uuid)
            ");
        }
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX customer_addresses_uuid_unique
            ON customer_addresses
        ");
    }
};
