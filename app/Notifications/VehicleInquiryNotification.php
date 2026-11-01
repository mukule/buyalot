<?php

namespace App\Notifications;

use App\Models\VehicleInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class VehicleInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly VehicleInquiry $inquiry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $product = $this->inquiry->product;
        $contact = collect([$this->inquiry->phone, $this->inquiry->email])->filter()->join(' · ');

        $vehicle = $product?->name ?? 'your vehicle';
        $who     = $this->inquiry->name . ($contact ? " ({$contact})" : '');

        [$title, $body] = match ($this->inquiry->type) {
            VehicleInquiry::TYPE_CALLBACK => ["Call-back request: {$vehicle}", "{$who} asked you to call them back."],
            VehicleInquiry::TYPE_OFFER    => [
                "New offer: {$vehicle}",
                "{$who} offered KSh " . number_format((float) $this->inquiry->offer_amount) . '.',
            ],
            default => ["New inquiry: {$vehicle}", "{$who}:"],
        };
        if (filled($this->inquiry->message)) {
            $body .= ' ' . Str::limit($this->inquiry->message, 300);
        }

        return [
            'type'         => 'vehicle_inquiry',
            'inquiry_type' => $this->inquiry->type,
            'title'        => $title,
            'body'         => $body,
            'offer_amount' => $this->inquiry->offer_amount !== null ? (float) $this->inquiry->offer_amount : null,
            'action_url'   => $product?->slug ? '/products/' . $product->slug : null,
            'inquiry_id'   => $this->inquiry->id,
            'product_id'   => $this->inquiry->product_id,
            'buyer_name'   => $this->inquiry->name,
            'buyer_email'  => $this->inquiry->email,
            'buyer_phone'  => $this->inquiry->phone,
            'message'      => $this->inquiry->message,
        ];
    }
}
