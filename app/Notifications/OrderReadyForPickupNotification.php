<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderReadyForPickupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $pickupName,
        private readonly ?string $pickupAddress = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $body = "Your order #{$this->order->order_code} is ready for pickup at {$this->pickupName}";
        if ($this->pickupAddress) {
            $body .= " ({$this->pickupAddress})";
        }
        $body .= '.';

        return [
            'type'        => 'order_ready_for_pickup',
            'title'       => 'Order Ready for Pickup',
            'body'        => $body,
            'action_url'  => '/orders/' . $this->order->ulid,
            'order_code'  => $this->order->order_code,
            'order_ulid'  => $this->order->ulid,
            'pickup_name' => $this->pickupName,
            'pickup_address' => $this->pickupAddress,
        ];
    }
}
