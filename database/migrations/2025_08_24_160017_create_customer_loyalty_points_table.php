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
        Schema::create('customer_loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->enum('transaction_type', ['earned', 'redeemed', 'expired', 'adjusted', 'imported'])->default('earned');
            $table->string('reason'); // Purchase, referral, birthday
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable(); // orders, referrals
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('monetary_value', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['customer_id', 'transaction_type']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['expires_at', 'is_expired']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_points');
    }
};
