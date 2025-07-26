<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('seller_applications', function (Blueprint $table) {
        $table->tinyInteger('status')->default(0)->after('is_submitted')->comment('0 = pending, 1 = approved, 2 = rejected');
        $table->text('status_reason')->nullable()->after('status')->comment('Reason for rejection or status explanation');
    });
}

public function down()
{
    Schema::table('seller_applications', function (Blueprint $table) {
        $table->dropColumn(['status', 'status_reason']);
    });
}

};
