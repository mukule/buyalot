<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the column if it already exists
        if (Schema::hasColumn('policies', 'code')) {
            // Drop index if it exists
            try {
                DB::statement('ALTER TABLE policies DROP INDEX IF EXISTS policies_code_unique');
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }

            Schema::table('policies', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }

        // Add new nullable code column
        Schema::table('policies', function (Blueprint $table) {
            $table->string('code')->nullable()->after('title');
        });

        // Fill existing policies with lowercase underscore code
        DB::table('policies')->update([
            'code' => DB::raw("LOWER(REPLACE(title, ' ', '_'))")
        ]);

        // Make code non-nullable and unique
        Schema::table('policies', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('policies', 'code')) {
            try {
                DB::statement('ALTER TABLE policies DROP INDEX IF EXISTS policies_code_unique');
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }

            Schema::table('policies', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
