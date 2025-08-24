<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('customer_number')->unique(); // Auto-generated customer ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('profile_photo')->nullable();

            // Business information (for B2B customers)
            $table->string('company_name')->nullable();
            $table->string('company_registration')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();

            // Customer categorization
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'suspended', 'blacklisted'])->default('active');
            $table->string('customer_segment')->nullable(); // VIP, Regular, Bronze, Silver, Gold, etc.
            $table->json('tags')->nullable(); // Flexible tagging system

            // Marketing preferences
            $table->boolean('accepts_marketing')->default(true);
            $table->boolean('accepts_sms')->default(false);
            $table->boolean('accepts_phone_calls')->default(false);
            $table->json('marketing_preferences')->nullable(); // Detailed preferences

            // Customer lifecycle
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->integer('lifetime_days')->nullable(); // Days since first order

            // Account management
            $table->string('acquisition_source')->nullable(); // How they found the seller
            $table->string('referrer_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->json('metadata')->nullable(); // Additional custom data

            // Authentication (if customers can log in)
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('last_login_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['seller_id', 'email']);
            $table->index(['seller_id', 'status']);
            $table->index(['seller_id', 'customer_type']);
            $table->index(['seller_id', 'customer_segment']);
            $table->index(['total_spent', 'seller_id']);
            $table->index(['last_order_at', 'seller_id']);
            $table->index('customer_number');
            $table->unique(['seller_id', 'email']); // Email unique per seller
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
