<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            // Hierarchy support
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('level')->default('region'); // region, subregion, area, route

            $table->foreign('parent_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['level']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
           $table->dropColumn('parent_id');
           $table->dropColumn('level');

        });
    }
};
