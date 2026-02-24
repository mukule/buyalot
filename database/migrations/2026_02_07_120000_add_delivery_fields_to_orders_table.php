<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_id')) {
                $table->foreignId('delivery_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'delivery_assignment_status')) {
                $table->string('delivery_assignment_status', 20)->nullable()->after('delivery_id'); // pending, accepted, rejected
            }
            if (!Schema::hasColumn('orders', 'delivery_rejection_reason')) {
                $table->text('delivery_rejection_reason')->nullable()->after('delivery_assignment_status');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_status'); // mobile_money, card, cash_on_delivery, etc.
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_id')) {
                $table->dropForeign(['delivery_id']);
                $table->dropColumn('delivery_id');
            }
            foreach (['delivery_assignment_status', 'delivery_rejection_reason', 'payment_method'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
