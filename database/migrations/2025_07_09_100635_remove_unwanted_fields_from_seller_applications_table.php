<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnwantedFieldsFromSellerApplicationsTable extends Migration
{
    public function up()
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn([
                'drivers_license',
                'email',
                'phone',
                'website',
                'product_categories',
                'business_name',
            ]);
        });
    }

    public function down()
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            // Restore dropped columns (optional — define same types as before)
            $table->string('drivers_license')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('product_categories')->nullable();
            $table->string('business_name')->nullable();
        });
    }
}
