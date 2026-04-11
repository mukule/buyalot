<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderAssignedToDeliveryNotification extends Notification implements ShouldQueue
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
            'type'       => 'delivery_assigned',
            'title'      => 'New Delivery Assignment',
            'body'       => "You have been assigned to deliver Order #{$this->order->order_code}. Please review and accept or reject the assignment.",
            'action_url' => '/admin/delivery',
            'order_code' => $this->order->order_code,
            'order_ulid' => $this->order->ulid,
        ];
    }
}
