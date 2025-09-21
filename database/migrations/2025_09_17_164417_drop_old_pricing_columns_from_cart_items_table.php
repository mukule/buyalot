<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['regular_price', 'selling_price', 'discount']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('regular_price', 12, 2)->after('quantity');
            $table->decimal('selling_price', 12, 2)->after('regular_price');
            $table->decimal('discount', 12, 2)->default(0.00)->after('selling_price');
        });
    }
};
