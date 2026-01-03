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
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->boolean('show_product_images')->default(true)->after('require_admin_void');
            $table->string('product_display_design')->default('grid')->after('show_product_images'); // grid, small_grid, list
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn(['show_product_images', 'product_display_design']);
        });
    }
};
