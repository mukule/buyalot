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
        Schema::create('commission_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Starter", "Pro", "Enterprise"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annually'])->default('monthly');
            $table->decimal('base_fee', 10, 2)->default(0); // Fixed monthly/annual fee
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Store plan features
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // 2. Commission Rules - Dynamic commission calculation rules
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_plan_id')->constrained('commission_plans')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('calculation_type', ['percentage', 'fixed_per_item', 'fixed_per_order', 'tiered'])->default('percentage');
            $table->decimal('rate', 10, 4)->default(0);
            $table->decimal('min_charge', 10, 2)->default(0);
            $table->decimal('max_charge', 15, 2)->nullable();
            $table->decimal('free_threshold', 15, 2)->nullable();
            $table->boolean('compound_with_base_fee')->default(false);
            $table->json('calculation_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['commission_plan_id', 'is_active', 'priority']);
        });

        Schema::create('commission_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->onDelete('cascade');
            $table->string('condition_type'); // product_category,sale_amount, seller
            $table->string('operator'); // 'equals', 'greater_than', 'less_than', 'in', 'not_in', 'contains'
            $table->json('condition_value'); // Flexible value storage
            $table->enum('logic_operator', ['AND', 'OR'])->default('AND'); //combine with other conditions
            $table->timestamps();

            $table->index(['commission_rule_id']);
            $table->index(['condition_type']);
        });

        // tiered commission structures
        Schema::create('commission_rule_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->onDelete('cascade');
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('tier_rate', 10, 4); // Rate for this tier
            $table->decimal('tier_fixed_amount', 10, 2)->default(0); // Fixed amount for this tier
            $table->integer('tier_order')->default(1);
            $table->string('tier_name')->nullable();
            $table->timestamps();

            $table->index(['commission_rule_id']);
            $table->index(['tier_order']);
        });

        // 5. Seller Subscriptions - Track which plan each seller is on
        Schema::create('seller_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade'); // Assuming sellers are users
            $table->foreignId('commission_plan_id')->constrained('commission_plans')->onDelete('cascade');
            $table->decimal('custom_base_fee', 10, 2)->nullable(); // Override plan base fee
            $table->json('custom_rates')->nullable(); // Override specific rule rates
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional subscription data
            $table->timestamps();

            $table->index(['seller_id']);
            $table->index(['status']);
            $table->index(['commission_plan_id']);
        });

        // 6. Commission Calculations - Store calculated commissions from sales
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->onDelete('cascade');
            $table->string('calculable_type'); // 'orders', 'subscriptions', 'products'
            $table->unsignedBigInteger('calculable_id'); // The ID of the sale/order/transaction
            $table->decimal('sale_amount', 15, 2); // Original sale amount
            $table->decimal('commission_base', 15, 2); // Amount used for commission calculation
            $table->decimal('commission_rate', 10, 4); // Applied rate
            $table->decimal('commission_amount', 15, 2); // Calculated commission
            $table->json('calculation_details')->nullable(); // Store breakdown of how commission was calculated
            $table->enum('status', ['pending', 'confirmed', 'disputed', 'refunded'])->default('pending');
            $table->timestamp('calculated_at');
            $table->timestamp('due_at')->nullable(); // When commission payment is due
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['calculable_type', 'calculable_id']);
            $table->index(['calculated_at', 'status']);
        });

        // 7. Commission Invoices - Group commissions into billing periods
        Schema::create('commission_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('commission_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('invoice_data')->nullable(); // Store complete invoice details
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->index('due_at');
        });

        // 8. Commission Invoice Items - Link calculations to invoices
        Schema::create('commission_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_invoice_id')->constrained('commission_invoices')->onDelete('cascade');
            $table->foreignId('commission_calculation_id')->constrained('commission_calculations')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['commission_invoice_id','commission_calculation_id'], 'invoice_calculation_unique');
        });

        // 9. Commission Payments - Track actual payments received
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_invoice_id')->constrained('commission_invoices')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['stripe', 'paypal', 'bank_transfer', 'wallet', 'manual'])->default('stripe');
            $table->string('transaction_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('payment_data')->nullable(); // Store gateway response data
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['commission_invoice_id']);
            $table->index(['status']);
            $table->index('transaction_id');
        });

        // 10. Commission Adjustments - Manual adjustments (discounts, bonuses, corrections)
        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('commission_invoice_id')->nullable()->constrained('commission_invoices')->onDelete('cascade');
            $table->decimal('amount', 15, 2); // positive for additional charges, negative for discounts
            $table->enum('type', ['discount', 'bonus_charge', 'correction', 'refund', 'penalty'])->default('discount');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['type', 'status']);
        });

        // 11. Commission Analytics - Pre-calculated metrics for performance
        Schema::create('commission_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('daily');
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_commission', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('average_commission_rate', 10, 4)->default(0);
            $table->json('metrics')->nullable(); // Store additional calculated metrics
            $table->timestamps();

            $table->unique(['seller_id', 'date', 'period_type'], 'seller_date_period_unique');
            $table->index(['date', 'period_type']);
        });
    }

        public function down()
    {
        Schema::dropIfExists('commission_analytics');
        Schema::dropIfExists('commission_adjustments');
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('commission_invoice_items');
        Schema::dropIfExists('commission_invoices');
        Schema::dropIfExists('commission_calculations');
        Schema::dropIfExists('seller_subscriptions');
        Schema::dropIfExists('commission_rule_tiers');
        Schema::dropIfExists('commission_rule_conditions');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('commission_plans');
    }
};
