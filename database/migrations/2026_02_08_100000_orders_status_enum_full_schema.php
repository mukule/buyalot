<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure order status enum matches schema:
     * pending, confirmed, processing, on_hold, out_for_delivery, shipped, delivered,
     * returned, partially_returned, refunded, partially_refunded, cancelled, failed
     * fulfillment_status: unfulfilled, processing, partially_fulfilled, fulfilled, cancelled (unchanged)
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'processing',
            'on_hold',
            'out_for_delivery',
            'shipped',
            'delivered',
            'returned',
            'partially_returned',
            'refunded',
            'partially_refunded',
            'cancelled',
            'failed'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert to previous enum if needed; leave as no-op to avoid data loss
    }
};
