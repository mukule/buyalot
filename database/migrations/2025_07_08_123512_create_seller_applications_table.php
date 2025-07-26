<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('business_type')->nullable();
            $table->boolean('agreed_to_privacy')->default(false);

            // Contact info
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Identification
            $table->string('identification_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('drivers_license')->nullable();

            // Business info
            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Categories (stored as JSON array)
            $table->json('product_categories')->nullable();
            $table->string('primary_product_category')->nullable();
            $table->text('description')->nullable();

            // Owner info
            $table->string('owner_first_name')->nullable();
            $table->string('owner_last_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();

            // VAT & registration
            $table->string('vat_registered')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('company_legal_name')->nullable();
            $table->string('ke_business_reg_number')->nullable();          
            $table->string('non_ke_business_reg_number')->nullable();     
            $table->string('ke_id_number')->nullable();                   
            $table->string('passport_number_sp')->nullable();              

            $table->string('country')->nullable();
            $table->string('nationality')->nullable();
            $table->string('monthly_revenue')->nullable();

            // Business operations
            $table->string('owns_physical_store')->nullable();
            $table->integer('retail_store_count')->nullable();
            $table->string('is_supplier_to_retailers')->nullable();
            $table->string('operates_other_marketplaces')->nullable();
            $table->text('marketplace_details')->nullable();
            $table->integer('supplier_retail_count')->nullable();
            $table->integer('product_count')->nullable();
            $table->string('stock_handling')->nullable();
            $table->string('product_website')->nullable();
            $table->string('product_origin')->nullable();
            $table->string('owned_brands')->nullable();
            $table->string('licensed_brands')->nullable();
            $table->string('product_branding')->nullable();
            $table->string('social_media')->nullable();
            $table->text('business_summary')->nullable();

            // Discovery & referral
            $table->string('discovery_source')->nullable();
            $table->string('referrer_email')->nullable();
            $table->string('share_with_distributors')->nullable();

            // Progress tracking
            $table->boolean('is_submitted')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_applications');
    }
}
