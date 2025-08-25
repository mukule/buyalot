<?php

namespace App\Jobs;

use App\Models\Payment\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPaymentJob implements ShouldQueue
{
     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Payment $payment
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        if ($this->payment->isPending() || $this->payment->status->value === 'processing') {
            $paymentService->verifyPayment($this->payment);
        }
    }
}
