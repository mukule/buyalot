<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update the ENUM to include 'rejected'
        DB::statement("
           ALTER TABLE `warehouse_receivables`
        CHANGE `status` `status` enum('pending','received','rejected')
               COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT 'pending' AFTER `quantity`;
        ");
    }

    public function down()
    {
        // Rollback: remove 'rejected'
        DB::statement("
            ALTER TABLE warehouse_receivables
            MODIFY COLUMN status ENUM('pending', 'approved')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
