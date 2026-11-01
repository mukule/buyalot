<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - vehicle_inquiries.type: message | callback | offer (+ offer_amount)
 * - product_reports: buyers report a listing (scam, wrong info, sold, …) for admin review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inquiries', function (Blueprint $table) {
            $table->string('type', 20)->default('message')->after('seller_id');
            $table->decimal('offer_amount', 14, 2)->nullable()->after('message');
        });

        Schema::create('product_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 50);
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('open'); // open | reviewed | dismissed
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->unique(['product_id', 'user_id']); // one report per user per listing
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reports');

        Schema::table('vehicle_inquiries', function (Blueprint $table) {
            $table->dropColumn(['type', 'offer_amount']);
        });
    }
};
