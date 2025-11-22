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
        Schema::create('mpesa_requests', function (Blueprint $table) {
            $table->id();

            // Type of request (STK_PUSH, REVERSAL, C2B_REGISTER_URL, B2C)
            $table->string('request_type')->default('STK_PUSH');

            // Outgoing request details
            $table->string('phone')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('account_reference')->nullable(); //order_code
            $table->json('request_payload')->nullable();

            // Safaricom identifiers
            $table->string('checkout_request_id')->nullable();
            $table->string('merchant_request_id')->nullable();

            // Callback response
            $table->json('callback_payload')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();

            // Status of request/callback
            $table->enum('status', ['requested', 'callback_received', 'completed', 'failed'])
                ->default('requested');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_requests');
    }
};
