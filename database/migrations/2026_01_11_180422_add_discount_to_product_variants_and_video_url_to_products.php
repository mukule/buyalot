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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('selling_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
