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
        Schema::table('payments', function (Blueprint $table) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('amount_paid', 15, 2)
                    ->default(0)
                    ->after('amount');
            });

            Schema::table('mpesa_payments', function (Blueprint $table) {
                $table->decimal('amount_paid', 15, 2)->default(0)
                    ->after('amount');
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('amount_paid', 15, 2)->default(0.00)->after('total_amount');
                $table->decimal('balance', 15, 2)->default(0.00)->after('amount_paid');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });

        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
            $table->dropColumn('balance');
        });
    }
};
