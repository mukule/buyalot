<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CommissionCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateCommissionJob implements ShouldQueue
{ use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private int $sellerId,
        private array $saleData
    ) {}

    public function handle(CommissionCalculatorService $calculator): void
    {
        try {
            $seller = User::findOrFail($this->sellerId);

            $calculation = $calculator->calculateForSeller($seller, $this->saleData);

            if ($calculation) {
                Log::info("Commission calculated", [
                    'seller_id' => $this->sellerId,
                    'calculation_id' => $calculation->id,
                    'amount' => $calculation->commission_amount,
                ]);
            } else {
                Log::info("No commission calculated - no applicable rules", [
                    'seller_id' => $this->sellerId,
                    'sale_data' => $this->saleData,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Commission calculation failed", [
                'seller_id' => $this->sellerId,
                'error' => $e->getMessage(),
                'sale_data' => $this->saleData,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Commission calculation job failed permanently", [
            'seller_id' => $this->sellerId,
            'error' => $exception->getMessage(),
            'sale_data' => $this->saleData,
        ]);
    }
}
