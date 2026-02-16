<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * KYC and transport details for delivery person self-registration.
     */
    public function up(): void
    {
        Schema::create('delivery_person_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Contact (also on user; kept for application record)
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();

            // KYC
            $table->string('id_number');
            $table->string('id_copy_path'); // stored path to uploaded ID copy
            $table->string('kra_pin');
            $table->string('kra_copy_path'); // stored path to uploaded KRA copy

            // Where they stay/live
            $table->text('address');

            // Transport
            $table->string('transport_type'); // car, truck, motorbike, bicycle, electric_bike, etc.
            $table->string('transport_registration_number')->nullable();
            $table->json('transport_details')->nullable(); // extra details (make, model, etc.)

            // Approval
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_person_applications');
    }
};
