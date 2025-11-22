<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('pickup_point_id')->nullable()->after('id');

            $table->foreign('pickup_point_id')
                ->references('id')
                ->on('pickup_points')
                ->onDelete('set null'); 
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['pickup_point_id']);
            $table->dropColumn('pickup_point_id');
        });
    }
};
