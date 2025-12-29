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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('checkout_session_id')->nullable()->after('customer_id');

            $table->foreign('checkout_session_id')
                  ->references('id')
                  ->on('checkout_sessions')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['checkout_session_id']);
            $table->dropColumn('checkout_session_id');
        });
    }
};
