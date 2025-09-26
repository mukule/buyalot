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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->after('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->after('phone')->nullable();
            $table->boolean('status')->after('name')->default(true);
            $table->string('gender')->after('last_login_at')->nullable();
            $table->string('avatar')->after('gender')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('phone');
            $table->dropColumn('last_login_at');
            $table->dropColumn('status');
            $table->dropColumn('gender');
            $table->dropColumn('avatar');
        });
    }
};
