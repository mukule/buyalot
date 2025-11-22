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
//        Schema::table('pickup_points', function (Blueprint $table) {
//            // Rename existing column
//            if (Schema::hasColumn('pickup_points', 'contact_number')) {
//                $table->renameColumn('contact_number', 'contact_phone');
//            }
//
//            // Add new columns if they don’t exist
//            if (!Schema::hasColumn('pickup_points', 'code')) {
//                $table->string('code')->unique()->after('name');
//            }
//
//            if (!Schema::hasColumn('pickup_points', 'description')) {
//                $table->text('description')->nullable()->after('code');
//            }
//
//            if (Schema::hasColumn('pickup_points', 'address')) {
//                $table->text('address')->change(); // make it text for map URLs
//            }
//
//            if (!Schema::hasColumn('pickup_points', 'contact_email')) {
//                $table->string('contact_email')->nullable()->after('contact_phone');
//            }
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_points', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_points', 'contact_email')) {
                $table->dropColumn('contact_email');
            }

            if (Schema::hasColumn('pickup_points', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('pickup_points', 'code')) {
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('pickup_points', 'contact_phone')) {
                $table->renameColumn('contact_phone', 'contact_number');
            }

            if (Schema::hasColumn('pickup_points', 'address')) {
                $table->string('address', 255)->change();
            }
        });
    }
};
