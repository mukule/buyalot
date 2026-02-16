<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryAccountSuspended extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $reason;
    public string $logoUrl;
    public string $appName;

    public function __construct(User $user, string $reason)
    {
        $this->user = $user;
        $this->reason = $reason;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    public function build(): self
    {
        return $this->markdown('emails.delivery.account-suspended')
            ->subject('Your Delivery Partner Account Has Been Suspended')
            ->with([
                'user' => $this->user,
                'reason' => $this->reason,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
            ]);
    }
}
