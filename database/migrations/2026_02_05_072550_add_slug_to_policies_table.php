<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('code');
        });

        // Auto-generate slugs for existing policies
        $policies = DB::table('policies')->get();
        foreach ($policies as $policy) {
            $slug = Str::slug($policy->title);

            // Ensure uniqueness in case of duplicate titles
            $count = DB::table('policies')->where('slug', $slug)->count();
            if ($count > 0) {
                $slug .= '-' . ($policy->id); // append ID to make unique
            }

            DB::table('policies')->where('id', $policy->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
