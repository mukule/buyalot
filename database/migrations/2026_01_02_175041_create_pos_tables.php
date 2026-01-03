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
        Schema::create('pos_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_register_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('cash_sales_total', 12, 2)->default(0);
            $table->decimal('mpesa_sales_total', 12, 2)->default(0);
            $table->decimal('other_sales_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('pos_session_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pos_session_id']);
            $table->dropColumn('pos_session_id');
        });
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('pos_registers');
    }
};
