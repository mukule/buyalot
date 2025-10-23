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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            // Order Status
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'shipped',
                'delivered', 'cancelled', 'refunded', 'partially_refunded'
            ])->default('pending');
            // Financial Information
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->foreignId('billing_address_id')->nullable();
            $table->foreignId('shipping_address_id')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Custom fields, tracking info
            $table->string('source')->default('web'); // web, mobile, api, admin
            $table->string('channel')->nullable();

            // Fulfillment
            $table->enum('fulfillment_status', [
                'unfulfilled','processing','partially_fulfilled', 'fulfilled', 'cancelled'
            ])->default('unfulfilled');
            $table->json('fulfillment_info')->nullable();

            // Payment
            $table->enum('payment_status', [
                'pending', 'paid', 'partially_paid', 'failed', 'refunded', 'partially_refunded'
            ])->default('pending');

            // Discounts & Coupons
            $table->json('applied_discounts')->nullable();
            $table->foreignId('discount_id')->nullable();
            $table->string('coupon_code')->nullable();

            // Tracking
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status','payment_status']);
            $table->index(['created_at','fulfillment_status']);
            $table->index('order_code',);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
