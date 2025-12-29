<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->decimal('cart_amount', 12, 2)->default(0)->after('cart_id');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('cart_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('shipping_amount', 12, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'cart_amount',
                'discount_amount',
                'tax_amount',
                'shipping_amount',
            ]);
        });
    }
};
