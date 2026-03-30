<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewOrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'new_order',
            'title'       => 'New Order Received',
            'body'        => "Order #{$this->order->order_code} has been placed. Total: KSh " . number_format((float) $this->order->total_amount, 2),
            'action_url'  => '/admin/orders/' . $this->order->ulid,
            'order_code'  => $this->order->order_code,
            'order_ulid'  => $this->order->ulid,
            'total_amount' => (float) $this->order->total_amount,
        ];
    }
}
