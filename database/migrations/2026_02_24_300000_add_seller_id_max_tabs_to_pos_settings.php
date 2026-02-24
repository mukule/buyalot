<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->foreignId('seller_id')
                ->nullable()
                ->after('id')
                ->constrained('seller_applications')
                ->nullOnDelete();

            $table->unsignedSmallInteger('max_tabs')
                ->default(1)
                ->after('show_product_images');

            $table->unsignedBigInteger('settings_version')
                ->default(1)
                ->after('metadata');

            $table->unique('seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropUnique(['seller_id']);
            $table->dropConstrainedForeignId('seller_id');
            $table->dropColumn(['max_tabs', 'settings_version']);
        });
    }
};
