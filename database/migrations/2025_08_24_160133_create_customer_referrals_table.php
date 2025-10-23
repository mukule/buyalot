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
        Schema::create('customer_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('referred_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('referral_code')->unique();
            $table->string('referred_email')->nullable(); // Before they sign up
            $table->string('referred_phone')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->decimal('referrer_reward', 10, 2)->nullable();
            $table->decimal('referred_reward', 10, 2)->nullable();
            $table->enum('reward_type', ['points', 'discount', 'cash', 'credit'])->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('completion_criteria')->nullable(); // What needs to happen for completion
            $table->json('tracking_data')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referred_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_referrals');
    }
};
