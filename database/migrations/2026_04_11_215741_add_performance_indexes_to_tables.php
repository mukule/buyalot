<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'idx_products_category_status_slug')) {
                $table->index(['category_id', 'status_id', 'slug'], 'idx_products_category_status_slug');
            }
            if (!$this->indexExists('products', 'idx_products_brand_status')) {
                $table->index(['brand_id', 'status_id'], 'idx_products_brand_status');
            }
            if (!$this->indexExists('products', 'idx_products_owner')) {
                $table->index(['owner_type', 'owner_id'], 'idx_products_owner');
            }
        });

        // Product variants table indexes
        Schema::table('product_variants', function (Blueprint $table) {
            if (!$this->indexExists('product_variants', 'idx_variants_product_price')) {
                $table->index(['product_id', 'selling_price'], 'idx_variants_product_price');
            }
            if (!$this->indexExists('product_variants', 'idx_variants_stock')) {
                $table->index(['stock'], 'idx_variants_stock');
            }
            if (!$this->indexExists('product_variants', 'idx_variants_sku')) {
                $table->index(['sku'], 'idx_variants_sku');
            }
        });

        // Cart items table indexes
        Schema::table('cart_items', function (Blueprint $table) {
            if (!$this->indexExists('cart_items', 'idx_cart_items_variant_cart')) {
                $table->index(['product_variant_id', 'cart_id'], 'idx_cart_items_variant_cart');
            }
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'idx_orders_customer_created')) {
                $table->index(['customer_id', 'created_at'], 'idx_orders_customer_created');
            }
            if (!$this->indexExists('orders', 'idx_orders_status')) {
                $table->index(['status'], 'idx_orders_status');
            }
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            if (!$this->indexExists('order_items', 'idx_order_items_order_variant')) {
                $table->index(['order_id', 'product_variant_id'], 'idx_order_items_order_variant');
            }
            if (!$this->indexExists('order_items', 'idx_order_items_seller')) {
                $table->index(['seller_id'], 'idx_order_items_seller');
            }
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            if (!$this->indexExists('categories', 'idx_categories_parent_active')) {
                $table->index(['parent_id', 'active'], 'idx_categories_parent_active');
            }
            if (!$this->indexExists('categories', 'idx_categories_slug')) {
                $table->index(['slug'], 'idx_categories_slug');
            }
        });

        // Warehouse product inventories table indexes
        Schema::table('warehouse_product_inventories', function (Blueprint $table) {
            if (!$this->indexExists('warehouse_product_inventories', 'idx_warehouse_inv_variant_wh_stock')) {
                $table->index(['product_variant_id', 'warehouse_id', 'stock'], 'idx_warehouse_inv_variant_wh_stock');
            }
            if (!$this->indexExists('warehouse_product_inventories', 'idx_warehouse_inv_warehouse')) {
                $table->index(['warehouse_id'], 'idx_warehouse_inv_warehouse');
            }
        });

        // Brands table indexes
        Schema::table('brands', function (Blueprint $table) {
            if (!$this->indexExists('brands', 'idx_brands_active')) {
                $table->index(['active'], 'idx_brands_active');
            }
            if (!$this->indexExists('brands', 'idx_brands_slug')) {
                $table->index(['slug'], 'idx_brands_slug');
            }
        });

        // Wishlists table indexes
        Schema::table('wishlists', function (Blueprint $table) {
            if (!$this->indexExists('wishlists', 'idx_wishlists_user')) {
                $table->index(['user_id'], 'idx_wishlists_user');
            }
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->indexExists('payments', 'idx_payments_status_created')) {
                $table->index(['status', 'created_at'], 'idx_payments_status_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Products table
        Schema::table('products', function (Blueprint $table) {
            if ($this->indexExists('products', 'idx_products_category_status_slug')) {
                $table->dropIndex('idx_products_category_status_slug');
            }
            if ($this->indexExists('products', 'idx_products_brand_status')) {
                $table->dropIndex('idx_products_brand_status');
            }
            if ($this->indexExists('products', 'idx_products_owner')) {
                $table->dropIndex('idx_products_owner');
            }
        });

        // Product variants table
        Schema::table('product_variants', function (Blueprint $table) {
            if ($this->indexExists('product_variants', 'idx_variants_product_price')) {
                $table->dropIndex('idx_variants_product_price');
            }
            if ($this->indexExists('product_variants', 'idx_variants_stock')) {
                $table->dropIndex('idx_variants_stock');
            }
            if ($this->indexExists('product_variants', 'idx_variants_sku')) {
                $table->dropIndex('idx_variants_sku');
            }
        });

        // Cart items table
        Schema::table('cart_items', function (Blueprint $table) {
            if ($this->indexExists('cart_items', 'idx_cart_items_variant_cart')) {
                $table->dropIndex('idx_cart_items_variant_cart');
            }
        });

        // Orders table
        Schema::table('orders', function (Blueprint $table) {
            if ($this->indexExists('orders', 'idx_orders_customer_created')) {
                $table->dropIndex('idx_orders_customer_created');
            }
            if ($this->indexExists('orders', 'idx_orders_status')) {
                $table->dropIndex('idx_orders_status');
            }
        });

        // Order items table
        Schema::table('order_items', function (Blueprint $table) {
            if ($this->indexExists('order_items', 'idx_order_items_order_variant')) {
                $table->dropIndex('idx_order_items_order_variant');
            }
            if ($this->indexExists('order_items', 'idx_order_items_seller')) {
                $table->dropIndex('idx_order_items_seller');
            }
        });

        // Categories table
        Schema::table('categories', function (Blueprint $table) {
            if ($this->indexExists('categories', 'idx_categories_parent_active')) {
                $table->dropIndex('idx_categories_parent_active');
            }
            if ($this->indexExists('categories', 'idx_categories_slug')) {
                $table->dropIndex('idx_categories_slug');
            }
        });

        // Warehouse product inventories table
        Schema::table('warehouse_product_inventories', function (Blueprint $table) {
            if ($this->indexExists('warehouse_product_inventories', 'idx_warehouse_inv_variant_wh_stock')) {
                $table->dropIndex('idx_warehouse_inv_variant_wh_stock');
            }
            if ($this->indexExists('warehouse_product_inventories', 'idx_warehouse_inv_warehouse')) {
                $table->dropIndex('idx_warehouse_inv_warehouse');
            }
        });

        // Brands table
        Schema::table('brands', function (Blueprint $table) {
            if ($this->indexExists('brands', 'idx_brands_active')) {
                $table->dropIndex('idx_brands_active');
            }
            if ($this->indexExists('brands', 'idx_brands_slug')) {
                $table->dropIndex('idx_brands_slug');
            }
        });

        // Wishlists table
        Schema::table('wishlists', function (Blueprint $table) {
            if ($this->indexExists('wishlists', 'idx_wishlists_user')) {
                $table->dropIndex('idx_wishlists_user');
            }
        });

        // Payments table
        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'idx_payments_status_created')) {
                $table->dropIndex('idx_payments_status_created');
            }
        });
    }
};
