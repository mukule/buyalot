<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('warehouse_id')
                ->constrained('orders')->nullOnDelete();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('dispatching_warehouse_id')->nullable()->after('pickup_warehouse_id')
                ->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['dispatching_warehouse_id']);
        });
    }
};
