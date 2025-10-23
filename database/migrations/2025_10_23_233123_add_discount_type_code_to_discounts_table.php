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
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('no_time_limit')->nullable()->after('expires_at');
            $table->string('discount_type_code')->nullable()->after('slug');
             $table->foreign('discount_type_code')
                   ->references('code')->on('discount_types')
                   ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'discount_type_code')) {
                $table->dropColumn('discount_type_code');
                $table->dropColumn('no_time_limit');
            }
        });
    }
};
