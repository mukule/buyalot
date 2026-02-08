<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('pickup_warehouse_id')->nullable()->after('pickup_point_id');

            $table->foreign('pickup_warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['pickup_warehouse_id']);
            $table->dropColumn('pickup_warehouse_id');
        });
    }
};
