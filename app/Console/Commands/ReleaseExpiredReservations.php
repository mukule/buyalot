<?php

namespace App\Console\Commands;

use App\Models\Cart\CartReservation;
use App\Models\Products\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release-expired';
    protected $description = 'Return stock to variants whose cart reservations have expired';

    public function handle(): int
    {
        $released = 0;
        $failed   = 0;

        // Chunk to avoid loading thousands of rows at once
        CartReservation::where('expires_at', '<=', now())
            ->chunkById(100, function ($reservations) use (&$released, &$failed) {
                foreach ($reservations as $reservation) {
                    try {
                        DB::transaction(function () use ($reservation) {
                            // Re-fetch with a lock to prevent race conditions with other
                            // processes that might be extending the same reservation.
                            $locked = CartReservation::lockForUpdate()->find($reservation->id);
                            if (!$locked || $locked->expires_at > now()) {
                                // Already extended by a retry or already deleted — skip.
                                return;
                            }
                            ProductVariant::where('id', $locked->product_variant_id)
                                ->increment('stock', $locked->quantity);
                            $locked->delete();
                        });
                        $released++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error('Failed to release expired reservation', [
                            'reservation_id' => $reservation->id,
                            'error'          => $e->getMessage(),
                        ]);
                    }
                }
            });

        if ($released > 0 || $failed > 0) {
            Log::info("Expired reservations released: {$released}, failed: {$failed}");
        }

        return self::SUCCESS;
    }
}
