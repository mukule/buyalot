<?php

namespace App\Console\Commands;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentStatus;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class VerifyPendingPayments extends Command
{
    protected $signature = 'payments:verify-pending';
    protected $description = 'Verify pending payments and update their status';

    public function handle(PaymentService $paymentService): int
    {
        $pendingPayments = Payment::where('status', PaymentStatus::PROCESSING)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        $this->info("Found {$pendingPayments->count()} pending payments to verify");

        $verified = 0;
        $failed = 0;

        foreach ($pendingPayments as $payment) {
            $response = $paymentService->verifyPayment($payment);

            if ($response->success && $payment->fresh()->isCompleted()) {
                $verified++;
                $this->info("Payment {$payment->ulid} verified as completed");
            } elseif ($payment->fresh()->isFailed()) {
                $failed++;
                $this->warn("Payment {$payment->ulid} marked as failed");
            }
        }

        $this->info("Verification complete: {$verified} completed, {$failed} failed");
        return CommandAlias::SUCCESS;
    }
}
