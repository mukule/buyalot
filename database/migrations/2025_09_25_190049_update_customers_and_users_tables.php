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
//        Schema::table('customers', function (Blueprint $table) {
////            $table->dropColumn(['google_id', 'provider', 'provider_verified_at','last_login_at','password','email_verified_at']);
//            $table->foreignId('user_id')
//                ->nullable()
//                ->constrained()
//                ->onDelete('set null');
//        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('customers', function (Blueprint $table) {
//            $table->string('google_id')->nullable();
//            $table->string('provider')->nullable();
//            $table->timestamp('provider_verified_at')->nullable();
//        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable();
        });
    }
};
