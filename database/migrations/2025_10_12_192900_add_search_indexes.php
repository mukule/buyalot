<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Commonly filtered and searched columns
            $table->index('status_id', 'idx_products_status_id');
            $table->index('name', 'idx_products_name');
            $table->index('slug', 'idx_products_slug');
            $table->index('product_code', 'idx_products_product_code');
            $table->index('meta_keywords', 'idx_products_meta_keywords');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status_id');
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_slug');
            $table->dropIndex('idx_products_product_code');
            $table->dropIndex('idx_products_meta_keywords');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_product_variants_product_id');
            $table->dropIndex('idx_product_variants_sku');
        });
    }
};
