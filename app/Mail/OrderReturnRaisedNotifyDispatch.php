<?php

namespace App\Mail;

use App\Models\Orders\Order;
use App\Models\Orders\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReturnRaisedNotifyDispatch extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderReturn $orderReturn,
        public string $reasonLabel,
        public bool $isFullReturn
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Return raised for Order #' . $this->order->order_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.order_return_raised_notify_dispatch',
        );
    }
}
