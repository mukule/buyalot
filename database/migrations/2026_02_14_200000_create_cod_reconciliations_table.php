<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cod_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('users')->nullOnDelete(); // delivery person who handed over
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete(); // pickup/dispatch warehouse
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('KES');
            $table->timestamp('reconciled_at')->nullable(); // when delivery person marked they handed over
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_reconciliations');
    }
};
