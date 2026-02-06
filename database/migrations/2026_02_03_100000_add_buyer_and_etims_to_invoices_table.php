<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('buyer_id');
            $table->string('buyer_kra_pin', 32)->nullable()->after('buyer_name');
            $table->string('buyer_email')->nullable()->after('buyer_kra_pin');
            $table->string('buyer_phone', 32)->nullable()->after('buyer_email');
            $table->string('etims_status', 32)->default('pending')->after('meta'); // pending, signed, failed
            $table->string('etims_reference', 128)->nullable()->after('etims_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_name', 'buyer_kra_pin', 'buyer_email', 'buyer_phone',
                'etims_status', 'etims_reference',
            ]);
        });
    }
};
