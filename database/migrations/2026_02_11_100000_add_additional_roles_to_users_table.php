<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Supports users with multiple portal roles (customer, seller, distributor).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('additional_roles')->nullable()->after('secondary_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('additional_roles');
        });
    }
};
