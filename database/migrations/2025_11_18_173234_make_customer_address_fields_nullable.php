<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
            $table->string('label')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('company')->nullable()->change();
            $table->string('address_line_1')->nullable()->change();
            $table->string('address_line_2')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('state_province')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
            $table->string('country_code')->nullable()->change();
            $table->string('country_name')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
            $table->tinyInteger('is_default')->nullable()->change();
            $table->tinyInteger('is_validated')->nullable()->change();
            $table->longText('validation_data')->nullable()->change();
            $table->text('delivery_instructions')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
            $table->string('label')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('company')->nullable(false)->change();
            $table->string('address_line_1')->nullable(false)->change();
            $table->string('address_line_2')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('state_province')->nullable(false)->change();
            $table->string('postal_code')->nullable(false)->change();
            $table->string('country_code')->nullable(false)->change();
            $table->string('country_name')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
            $table->tinyInteger('is_default')->nullable(false)->change();
            $table->tinyInteger('is_validated')->nullable(false)->change();
            $table->longText('validation_data')->nullable(false)->change();
            $table->text('delivery_instructions')->nullable(false)->change();
        });
    }
};
