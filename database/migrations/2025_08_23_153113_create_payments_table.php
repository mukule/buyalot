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
        Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->morphs('payable'); // orders, subscriptions
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('KES');
                $table->string('provider'); // mpesa, stripe, paypal,
                $table->string('method'); // card, mobile_money, bank_transfer
                $table->enum('status', [
                    'pending',
                    'processing',
                    'completed',
                    'failed',
                    'cancelled',
                    'refunded',
                    'expired'
                ])->default('pending');
                $table->string('reference')->unique()->nullable();
                $table->string('provider_reference')->nullable();
                $table->json('metadata')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['provider', 'status']);
                $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
