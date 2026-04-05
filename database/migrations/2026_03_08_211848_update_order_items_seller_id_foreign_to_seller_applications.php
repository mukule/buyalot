<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_seller_id_foreign');

            $table->foreign('seller_id')
                ->references('id')
                ->on('seller_applications')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);

            $table->index('seller_id');
        });
    }
};