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
        Schema::create('warehouse_product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();

            $table->integer('stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('damaged_stock')->default(0);

            $table->decimal('cost_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_variant_id'], 'wh_prod_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_product_inventories');
    }
};
