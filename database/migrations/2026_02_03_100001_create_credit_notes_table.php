<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('number')->unique();
            $table->bigInteger('amount_minor');
            $table->string('currency', 3);
            $table->text('reason')->nullable();
            $table->string('etims_status', 32)->default('pending');
            $table->string('etims_reference', 128)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
