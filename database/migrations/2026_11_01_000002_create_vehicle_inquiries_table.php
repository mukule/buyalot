<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer -> seller inquiries on vehicle listings (cars have no cart; buyers
 * inquire instead). Messages are screened for abusive language; a buyer who
 * sends one is blocked from sending further inquiries (users.inquiry_blocked_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // buyer
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete(); // listing owner
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('message');
            // delivered = sent to the seller; blocked = held back for abusive language
            $table->string('status', 20)->default('delivered');
            $table->boolean('moderation_checked')->default(false);
            $table->string('moderation_reason')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'inquiry_blocked_at')) {
                $table->timestamp('inquiry_blocked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_inquiries');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'inquiry_blocked_at')) {
                $table->dropColumn('inquiry_blocked_at');
            }
        });
    }
};
