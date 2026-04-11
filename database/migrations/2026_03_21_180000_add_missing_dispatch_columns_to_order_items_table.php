<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'dispatch_center_id')) {
                $table->unsignedBigInteger('dispatch_center_id')->nullable()->index()->after('dispatched_at');
                $table->foreign('dispatch_center_id')->references('id')->on('warehouses')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_items', 'dispatch_decline_reason')) {
                $table->string('dispatch_decline_reason')->nullable()->after('dispatch_center_id');
            }

            if (!Schema::hasColumn('order_items', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->index()->after('received_at');
                $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_items', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('received_by');
            }

            if (!Schema::hasColumn('order_items', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('order_items', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->index()->after('rejected_at');
                $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['dispatch_center_id']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'dispatch_center_id',
                'dispatch_decline_reason',
                'received_by',
                'rejection_reason',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};
