<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('delivery_type', 30)->default('customer_address')->after('delivery_id');
            $table->foreignId('pickup_warehouse_id')->nullable()->after('delivery_type')
                ->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['pickup_warehouse_id']);
            $table->dropColumn(['delivery_type', 'pickup_warehouse_id']);
        });
    }
};
