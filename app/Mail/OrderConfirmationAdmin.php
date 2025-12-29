<?php

namespace App\Mail;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject("New Order: {$this->order->order_code}")
                    ->view('emails.admin_notification')
                    ->with(['order' => $this->order]);
    }
}
