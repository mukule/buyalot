<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        // normalized orders table
        if (Schema::hasColumn('orders', 'delivery_id')) {
            $ordersWithDelivery = DB::table('orders')
                ->whereNotNull('delivery_id')
                ->select('id', 'delivery_id', 'delivery_assignment_status', 'delivery_rejection_reason', 'allocated_for_pickup_at', 'picked_at')
                ->get();

            foreach ($ordersWithDelivery as $row) {
                $status = $row->delivery_assignment_status ?? 'pending';
                DB::table('deliveries')->insertOrIgnore([
                    'order_id' => $row->id,
                    'delivery_id' => $row->delivery_id,
                    'assignment_status' => $status,
                    'rejection_reason' => $row->delivery_rejection_reason,
                    'allocated_for_pickup_at' => $row->allocated_for_pickup_at ?? null,
                    'picked_at' => $row->picked_at ?? null,
                    'delivered_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop delivery-related columns from orders
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = ['delivery_id', 'delivery_assignment_status', 'delivery_rejection_reason', 'allocated_for_pickup_at', 'picked_at'];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    if ($col === 'delivery_id') {
                        $table->dropForeign(['delivery_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Re-add columns to orders (simplified - no data migration back)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_id')) {
                $table->foreignId('delivery_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'delivery_assignment_status')) {
                $table->string('delivery_assignment_status', 20)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_rejection_reason')) {
                $table->text('delivery_rejection_reason')->nullable();
            }
            if (!Schema::hasColumn('orders', 'allocated_for_pickup_at')) {
                $table->timestamp('allocated_for_pickup_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'picked_at')) {
                $table->timestamp('picked_at')->nullable();
            }
        });
    }
};
