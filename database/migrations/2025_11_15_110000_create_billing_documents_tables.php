<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-seller, per-document-type, per-year sequences
        Schema::create('doc_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('doc_type', 32); // invoice, receipt
            $table->integer('year')->nullable(); // for yearly reset (null = global)
            $table->string('prefix', 64)->nullable();
            $table->unsignedBigInteger('next_seq')->default(1);
            $table->timestamps();
            $table->unique(['seller_id', 'doc_type', 'year']);
        });

        // Invoices table (covers tax_invoice, credit_note, debit_note, proforma, commercial)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('type', 32)->default('tax_invoice'); // tax_invoice, credit_note, debit_note, proforma, commercial
            $table->string('status', 32)->default('draft'); // draft, issued, sent, partially_paid, paid, voided, refunded, written_off
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('currency', 3);
            $table->decimal('fx_rate', 18, 8)->nullable(); // optional FX reference
            // monetary totals in minor units (integers)
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);
            $table->bigInteger('balance_minor')->default(0);
            $table->string('customer_po')->nullable();
            $table->string('reference')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'type', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->integer('quantity');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('discount_minor')->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0); // percent (e.g., 0.160000 for 16%)
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('line_total_minor'); // net + tax - discounts
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['invoice_id']);
        });

        // Receipts (payment, cash_sale, refund)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('type', 32)->default('payment_receipt'); // payment_receipt, cash_sale_receipt, refund_receipt
            $table->string('currency', 3);
            $table->bigInteger('amount_minor');
            $table->string('method', 32)->nullable(); // card, mpesa, bank_transfer, cash
            $table->string('external_ref')->nullable(); // PSP reference or idempotency key
            $table->dateTime('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'type']);
            $table->unique(['seller_id', 'external_ref']);
        });

        // Allocation of receipt amounts to invoices
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->bigInteger('amount_minor');
            $table->timestamps();
            $table->index(['receipt_id']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('doc_sequences');
    }
};
