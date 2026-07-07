<?php

namespace App\Jobs;

use App\Models\Orders\Order;
use App\Models\Seller\Seller;
use App\Models\User;
use App\Notifications\NewOrderPlacedNotification;
use App\Notifications\OrderItemsDispatchRequiredNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Fan out the in-app notifications raised when an order is placed:
 *  - one to every admin / super-admin
 *  - one to each seller with items in the order
 *
 * Dispatched from OrderPlacementService so the per-seller lookups and sends
 * stay off the checkout request once a real queue driver is configured.
 * Under the sync driver this still runs inline (no behavioural change).
 */
class SendOrderPlacedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int,int>  $sellerItemCounts  seller_id => item count
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $sellerItemCounts,
    ) {}

    public function handle(): void
    {
        try {
            // Notify all admin/super-admin users
            $adminUsers = User::role(['admin', 'super-admin'])->get();
            if ($adminUsers->isNotEmpty()) {
                Notification::send($adminUsers, new NewOrderPlacedNotification($this->order));
            }

            // Notify each seller whose items are in this order
            foreach ($this->sellerItemCounts as $sellerId => $itemCount) {
                $seller = Seller::find($sellerId);
                if (! $seller) {
                    continue;
                }
                $sellerUsers = $seller->users;
                if ($sellerUsers->isNotEmpty()) {
                    Notification::send($sellerUsers, new OrderItemsDispatchRequiredNotification($this->order, $itemCount));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send in-app order notifications', [
                'order_id' => $this->order->id,
                'error'    => $e->getMessage(),
                'stack'    => $e->getTraceAsString(),
            ]);
        }
    }
}
