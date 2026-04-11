<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_address_id')->nullable()->after('customer_id');
            $table->unsignedBigInteger('billing_address_id')->nullable()->after('shipping_address_id');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropColumn(['shipping_address_id', 'billing_address_id']);
        });
    }
};
