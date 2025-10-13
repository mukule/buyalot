<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('warehouse_product_inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_product_inventories', 'regular_price')) {
                $table->decimal('regular_price', 12, 2)->nullable()->after('damaged_stock');
            }
            if (!Schema::hasColumn('warehouse_product_inventories', 'selling_price')) {
                $table->decimal('selling_price', 12, 2)->nullable()->after('regular_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_product_inventories', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_product_inventories', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
            if (Schema::hasColumn('warehouse_product_inventories', 'regular_price')) {
                $table->dropColumn('regular_price');
            }
        });
    }
};
