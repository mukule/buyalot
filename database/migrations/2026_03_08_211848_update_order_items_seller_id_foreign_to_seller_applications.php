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
        Schema::table('order_items', function (Blueprint $table) {
            try {
                $table->dropForeign('order_items_seller_id_foreign');
            } catch (\Exception $e) {
            }
            $table->foreign('seller_id')
                ->references('id')
                ->on('seller_applications')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            try {
                $table->dropForeign('order_items_seller_id_foreign');
                $table->dropColumn('seller_id');
            } catch (\Exception $e) {
            }
            $table->foreign('seller_id')
                ->references('id')
                ->on('sellers')
                ->onDelete('cascade');
        });
    }
};
