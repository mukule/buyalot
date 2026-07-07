<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Which marketplace verticals this product is listed in (e.g. ["cars"]).
            // Empty/null = general "all products" (ecommerce default). A vertical
            // listing (cars/construction) shows products whose array contains its key.
            if (! Schema::hasColumn('products', 'marketplaces')) {
                $table->json('marketplaces')->nullable()->after('attributes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'marketplaces')) {
                $table->dropColumn('marketplaces');
            }
        });
    }
};
