<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderItemsDispatchRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly int $itemCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $itemLabel = $this->itemCount === 1 ? '1 item' : "{$this->itemCount} items";

        return [
            'type'       => 'dispatch_required',
            'title'      => 'Action Required: Dispatch Items',
            'body'       => "Order #{$this->order->order_code} contains {$itemLabel} awaiting dispatch. Please process and dispatch them.",
            'action_url' => '/admin/orders/' . $this->order->ulid,
            'order_code' => $this->order->order_code,
            'order_ulid' => $this->order->ulid,
            'item_count' => $this->itemCount,
        ];
    }
}
