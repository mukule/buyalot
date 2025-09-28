<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Add product & seller IDs
            $table->unsignedBigInteger('product_id')->after('cart_id');
            $table->unsignedBigInteger('seller_id')->nullable()->after('product_id');

            // Add normalized pricing
            $table->decimal('unit_price', 12, 2)->after('quantity');
            $table->decimal('total_price', 12, 2)->nullable()->after('unit_price');
            $table->decimal('discount_amount', 12, 2)->default(0.00)->after('total_price');

            // Optional JSON for flexibility
            $table->longText('metadata')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_id',
                'seller_id',
                'unit_price',
                'total_price',
                'discount_amount',
                'metadata',
            ]);
        });
    }
};
