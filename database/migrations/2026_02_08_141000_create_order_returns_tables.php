<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->string('raised_by_type', 30)->default('delivery_person'); // delivery_person, warehouse
            $table->unsignedBigInteger('raised_by_id')->nullable(); // user_id or warehouse_id
            $table->string('reason', 50); // breakages, expiry, wrong_items, spoiled, wrong_quantities, order_cancellation, pickup_point_closed, other
            $table->text('reason_notes')->nullable();
            $table->boolean('is_full_return')->default(true);
            $table->string('status', 30)->default('pending_receive'); // pending_receive, received_at_dispatch
            $table->timestamp('received_at_dispatch_at')->nullable();
            $table->foreignId('received_at_dispatch_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity_returned');
            $table->timestamps();

            $table->unique(['order_return_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
    }
};

