<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CommissionInvoiceService;
use Illuminate\Console\Command;

class GenerateCommissionInvoices extends Command
{
    protected $signature = 'commission:generate-invoices {--period=month} {--seller=}';
    protected $description = 'Generate commission invoices for sellers';

    public function __construct(
        private CommissionInvoiceService $invoiceService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = $this->option('period');
        $sellerId = $this->option('seller');

        $dateRange = $this->getDateRange($period);
        $sellers = $this->getSellers($sellerId);

        $this->info("Generating invoices for period: {$dateRange['start']} to {$dateRange['end']}");

        $generated = 0;
        foreach ($sellers as $seller) {
            try {
                $invoice = $this->invoiceService->generateInvoiceForPeriod(
                    $seller,
                    $dateRange['start'],
                    $dateRange['end']
                );

                if ($invoice->total_amount > 0) {
                    $this->info("Generated invoice #{$invoice->invoice_number} for {$seller->name} - \${$invoice->total_amount}");
                    $generated++;
                } else {
                    $invoice->delete(); // Remove zero-amount invoices
                }
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for {$seller->name}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully generated {$generated} invoices.");
        return 0;
    }

    private function getDateRange(string $period): array
    {
        return match($period) {
            'week' => [
                'start' => now()->subWeek()->startOfWeek(),
                'end' => now()->subWeek()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            'quarter' => [
                'start' => now()->subQuarter()->startOfQuarter(),
                'end' => now()->subQuarter()->endOfQuarter(),
            ],
            default => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
        };
    }

    private function getSellers($sellerId): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::whereHas('subscription', function($q) {
            $q->where('status', 'active');
        });

        if ($sellerId) {
            $query->where('id', $sellerId);
        }

        return $query->get();
    }
}
