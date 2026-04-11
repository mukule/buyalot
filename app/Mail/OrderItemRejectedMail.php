<?php

namespace App\Mail;

use App\Models\Orders\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderItemRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrderItem $orderItem;
    public string $reason;
    public string $logoUrl;
    public string $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(OrderItem $orderItem, string $reason)
    {
        $this->orderItem = $orderItem;
        $this->reason = OrderItem::REJECTION_REASONS[$reason] ?? $reason;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.seller.order-item-rejected')
            ->subject('Order Item Rejected')
            ->with([
                'orderItem' => $this->orderItem,
                'reason' => $this->reason,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
            ]);
    }
}
