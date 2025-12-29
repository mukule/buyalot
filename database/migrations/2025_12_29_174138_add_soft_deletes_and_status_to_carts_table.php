<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->softDeletes(); // adds deleted_at column
            $table->enum('status', ['active', 'ordered', 'abandoned'])
                  ->default('active')
                  ->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('status');
        });
    }
};
