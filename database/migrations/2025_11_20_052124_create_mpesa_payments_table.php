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
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();
            $table->foreignId('mpesa_request_id')
                ->nullable()
                ->constrained('mpesa_requests')
                ->nullOnDelete();
            $table->string('transaction_type')->default('C2B'); // STK, C2B, B2C
            $table->string('phone');
            $table->decimal('amount', 12, 2);
            $table->string('mpesa_receipt')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('account_reference')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_payments');
    }
};
