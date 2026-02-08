<?php

namespace App\Mail;

use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryOrderAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public User $deliveryUser,
        public string $deliveryNoteSummary,
        public string $loginUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivery assigned: Order #' . $this->order->order_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.delivery_order_assigned',
            with: [
                'order' => $this->order,
                'deliveryUser' => $this->deliveryUser,
                'deliveryNoteSummary' => $this->deliveryNoteSummary,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
