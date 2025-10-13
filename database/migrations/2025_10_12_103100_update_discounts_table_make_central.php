<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('discounts')) {
            return; // nothing to do
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'seller_id')) {
                // Drop FK then column if present
                try {
                    $table->dropConstrainedForeignId('seller_id');
                } catch (\Throwable $e) {
                    try { $table->dropForeign('discounts_seller_id_foreign'); } catch (\Throwable $e2) {}
                    try { $table->dropColumn('seller_id'); } catch (\Throwable $e3) {}
                }
            }
            if (!Schema::hasColumn('discounts', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('discounts', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('discounts', 'minimum_amount')) {
                $table->decimal('minimum_amount', 12, 2)->nullable()->after('value');
            }
            if (!Schema::hasColumn('discounts', 'maximum_discount')) {
                $table->decimal('maximum_discount', 12, 2)->nullable()->after('minimum_amount');
            }
            if (!Schema::hasColumn('discounts', 'usage_limit_per_customer')) {
                $table->integer('usage_limit_per_customer')->nullable()->after('usage_limit');
            }
            if (!Schema::hasColumn('discounts', 'used_count')) {
                $table->integer('used_count')->default(0)->after('usage_limit_per_customer');
            }
            if (!Schema::hasColumn('discounts', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('discounts', 'expires_at')) {
                $table->dateTime('expires_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('discounts', 'applicable_to')) {
                $table->string('applicable_to')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('discounts', 'conditions')) {
                $table->json('conditions')->nullable()->after('applicable_to');
            }
            if (!Schema::hasColumn('discounts', 'metadata')) {
                $table->json('metadata')->nullable()->after('conditions');
            }
            if (!Schema::hasColumn('discounts', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('metadata')->constrained('users')->nullOnDelete();
            }
        });

        // Migrate legacy data into new columns where appropriate
        try {
            // If legacy max_discount_amount exists, copy to maximum_discount when missing
            if (Schema::hasColumn('discounts', 'max_discount_amount') && Schema::hasColumn('discounts', 'maximum_discount')) {
                DB::statement('UPDATE discounts SET maximum_discount = COALESCE(maximum_discount, max_discount_amount)');
            }
            // If legacy per_user_limit exists, copy to usage_limit_per_customer when missing
            if (Schema::hasColumn('discounts', 'per_user_limit') && Schema::hasColumn('discounts', 'usage_limit_per_customer')) {
                DB::statement('UPDATE discounts SET usage_limit_per_customer = COALESCE(usage_limit_per_customer, per_user_limit)');
            }
            // If legacy times_used exists, copy to used_count when missing or zero
            if (Schema::hasColumn('discounts', 'times_used') && Schema::hasColumn('discounts', 'used_count')) {
                DB::statement('UPDATE discounts SET used_count = COALESCE(NULLIF(used_count, 0), times_used)');
            }
            // If legacy start_date/end_date exist, prefer them to starts_at/expires_at if the latter are null
            if (Schema::hasColumn('discounts', 'start_date') && Schema::hasColumn('discounts', 'starts_at')) {
                DB::statement('UPDATE discounts SET starts_at = COALESCE(starts_at, start_date)');
            }
            if (Schema::hasColumn('discounts', 'end_date') && Schema::hasColumn('discounts', 'expires_at')) {
                DB::statement('UPDATE discounts SET expires_at = COALESCE(expires_at, end_date)');
            }
        } catch (\Throwable $e) {
            // Non-critical: log in dev environments
            if (config('app.debug')) {
                logger('[UpdateDiscountsMigration] data backfill failed: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            // Re-add seller_id (nullable to avoid failure on rollback) and FK
            if (!Schema::hasColumn('discounts', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->constrained('users')->cascadeOnDelete();
            }

            // Drop added central columns
            if (Schema::hasColumn('discounts', 'created_by')) {
                try { $table->dropConstrainedForeignId('created_by'); } catch (\Throwable $e) { try { $table->dropColumn('created_by'); } catch (\Throwable $e2) {} }
            }
            if (Schema::hasColumn('discounts', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('discounts', 'conditions')) {
                $table->dropColumn('conditions');
            }
            if (Schema::hasColumn('discounts', 'applicable_to')) {
                $table->dropColumn('applicable_to');
            }
            if (Schema::hasColumn('discounts', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('discounts', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('discounts', 'used_count')) {
                $table->dropColumn('used_count');
            }
            if (Schema::hasColumn('discounts', 'usage_limit_per_customer')) {
                $table->dropColumn('usage_limit_per_customer');
            }
            if (Schema::hasColumn('discounts', 'maximum_discount')) {
                $table->dropColumn('maximum_discount');
            }
            if (Schema::hasColumn('discounts', 'minimum_amount')) {
                $table->dropColumn('minimum_amount');
            }
            if (Schema::hasColumn('discounts', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('discounts', 'slug')) {
                try { $table->dropUnique('discounts_slug_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('slug');
            }
        });
    }
};
