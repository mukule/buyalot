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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('dispatch_status')->default('pending')->index(); // pending, dispatched, declined, received, rejected
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('dispatch_center_id')->nullable()->index();
            $table->foreign('dispatch_center_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->string('dispatch_decline_reason')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable()->index();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['dispatch_center_id']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'dispatch_status',
                'dispatched_at',
                'dispatch_center_id',
                'dispatch_decline_reason',
                'received_at',
                'received_by',
                'rejection_reason',
                'rejected_at',
                'rejected_by'
            ]);
        });
    }
};
