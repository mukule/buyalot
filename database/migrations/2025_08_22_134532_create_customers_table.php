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
            $table->string('google_id')->nullable()->unique();
            $table->string('customer_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('profile_photo')->nullable();
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'suspended', 'blacklisted'])->default('active');
            $table->string('acquisition_source')->nullable();
            $table->string('referrer_url')->nullable();

            // Authentication fields
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('avatar')->nullable();
            $table->string('provider')->nullable(); // 'google', 'facebook', etc.
            $table->timestamp('provider_verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['id', 'email']);
            $table->index(['id', 'status']);
            $table->index(['id', 'customer_type']);
            $table->unique(['id', 'email']);
        });

        //  Business information
        Schema::create('customer_business_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_registration')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->timestamps();

            $table->index('customer_id');
        });

        //Customer segments
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // VIP, Regular, Bronze, Silver, Gold
            $table->string('description')->nullable();
            $table->json('criteria')->nullable(); // Conditions for auto-assignment
            $table->timestamps();
        });

        //Customer segment assignments
        Schema::create('customer_segment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_segment_id')->constrained('customer_segments')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'customer_segment_id'], 'customer_segment_unique');
        });

        // Customer tags (many-to-many)
        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('customer_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_tag_id')->constrained('customer_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_id', 'customer_tag_id']);
        });

            // Marketing preferences
        Schema::create('customer_marketing_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->boolean('accepts_marketing')->default(true);
            $table->boolean('accepts_sms')->default(false);
            $table->boolean('accepts_phone_calls')->default(false);
            $table->boolean('accepts_push_notifications')->default(true);
            $table->json('channel_preferences')->nullable(); // Detailed channel preferences
            $table->json('frequency_preferences')->nullable(); // How often they want to be contacted
            $table->json('content_preferences')->nullable(); // What type of content they want
            $table->timestamps();

            $table->unique('customer_id');
        });

            //Customer statistics
        Schema::create('customer_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->integer('lifetime_days')->nullable();
            $table->decimal('customer_lifetime_value', 15, 2)->default(0);
            $table->integer('days_since_last_order')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index(['total_spent', 'customer_id']);
            $table->index(['last_order_at', 'customer_id']);
        });

        // UTM and tracking data (separate table)
        Schema::create('customer_acquisition_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->json('additional_data')->nullable(); // For custom tracking parameters
            $table->timestamps();

            $table->unique('customer_id');
        });

            //Customer metadata for flexible key-value storage
        Schema::create('customer_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, etc.
            $table->timestamps();

            $table->unique(['customer_id', 'key']);
            $table->index('key');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customer_tag_assignments');
        Schema::dropIfExists('customer_marketing_preferences');
        Schema::dropIfExists('customer_statistics');
        Schema::dropIfExists('customer_acquisition_data');
        Schema::dropIfExists('customer_metadata');
        Schema::dropIfExists('customer_metadata_assignments');

    }
};
