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
            $table->string('request_type')->default('STK_PUSH');
            $table->string('phone')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('account_reference')->nullable();
            $table->json('request_payload')->nullable();
            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->string('status')->default('requested');
            $table->string('reference')->nullable();
            $table->string('request_code')->nullable();
            $table->string('currency')->default('KES');
            $table->string('provider')->default('mpesa')->nullable();
            $table->json('provider_request')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('method')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();
            $table->index(['payable_type', 'payable_id']);
            $table->index('reference');
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
