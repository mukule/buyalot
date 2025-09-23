<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variant_values', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variant_values', 'stock')) {
                $table->unsignedInteger('stock')->default(0)->after('variant_id');
            }

            if (!Schema::hasColumn('product_variant_values', 'regular_price')) {
                $table->decimal('regular_price', 10, 2)->default(0)->after('stock');
            }

            if (!Schema::hasColumn('product_variant_values', 'selling_price')) {
                $table->decimal('selling_price', 10, 2)->default(0)->after('regular_price');
            }
        });

        // Check if unique constraint exists using raw SQL
        $indexExists = DB::select("
        SELECT COUNT(*) as count
        FROM information_schema.statistics
        WHERE table_schema = ?
        AND table_name = 'product_variant_values'
        AND index_name = 'product_variant_values_product_id_variant_id_unique'
    ", [DB::getDatabaseName()]);

        if ($indexExists[0]->count == 0) {
            Schema::table('product_variant_values', function (Blueprint $table) {
                $table->unique(['product_id', 'variant_id']);
            });
        }
    }

    public function down(): void
    {
        // Check if unique constraint exists before dropping
        $indexExists = DB::select("
        SELECT COUNT(*) as count
        FROM information_schema.statistics
        WHERE table_schema = ?
        AND table_name = 'product_variant_values'
        AND index_name = 'product_variant_values_product_id_variant_id_unique'
    ", [DB::getDatabaseName()]);

        if ($indexExists[0]->count > 0) {
            Schema::table('product_variant_values', function (Blueprint $table) {
                $table->dropUnique(['product_id', 'variant_id']);
            });
        }

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropColumn(['stock', 'regular_price', 'selling_price']);
        });
    }
};
