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

           
            $sm = DB::getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('product_variant_values');

            if (!array_key_exists('product_variant_values_product_id_variant_id_unique', $indexes)) {
                $table->unique(['product_id', 'variant_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_values', function (Blueprint $table) {
            $sm = DB::getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('product_variant_values');

            if (array_key_exists('product_variant_values_product_id_variant_id_unique', $indexes)) {
                $table->dropUnique('product_variant_values_product_id_variant_id_unique');
            }

            $table->dropColumn(['stock', 'regular_price', 'selling_price']);
        });
    }
};
