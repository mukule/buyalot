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
            DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('user', 'customer', 'vendor', 'seller', 'admin') DEFAULT 'customer'");
            DB::table('users')
                ->where('user_type', 'vendor')
                ->update(['user_type' => 'seller']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('user', 'customer', 'vendor', 'seller') DEFAULT 'customer'");
            DB::table('users')
                ->where('user_type', 'seller')
                ->update(['user_type' => 'vendor']);
    }
};
