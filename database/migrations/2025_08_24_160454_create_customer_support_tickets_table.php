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
        Schema::create('customer_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed', 'cancelled'])->default('open');
            $table->string('category')->nullable(); // Technical, Billing, General, ordering
            $table->string('subcategory')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->json('tags')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('satisfaction_rating', 3, 2)->nullable(); // 1.00 - 5.00
            $table->text('satisfaction_feedback')->nullable();
            $table->integer('response_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['priority', 'status']);
            $table->index(['category', 'status']);
            $table->index('ticket_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_support_tickets');
    }
};
