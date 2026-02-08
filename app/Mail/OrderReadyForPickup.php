<?php

namespace App\Mail;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReadyForPickup extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $pickupLocationName,
        public ?string $pickupAddress = null,
        public ?string $pickupLocationDetail = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your order is ready for pickup – Order #' . $this->order->order_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.order_ready_for_pickup',
        );
    }
}
