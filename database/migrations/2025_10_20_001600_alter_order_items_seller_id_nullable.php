<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop the existing foreign key to modify the column
            try {
                $table->dropForeign(['seller_id']);
            } catch (\Throwable $e) {
                // ignore if it doesn't exist
            }

            // Make column nullable
            $table->foreignId('seller_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['seller_id']);
            } catch (\Throwable $e) {
                // ignore
            }
            // Revert to not nullable
            $table->foreignId('seller_id')->nullable(false)->change();
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
        });
    }
};
