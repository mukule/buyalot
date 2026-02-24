<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryApplicationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $loginUrl;
    public string $logoUrl;
    public string $appName;

    public function __construct(User $user, string $loginUrl)
    {
        $this->user = $user;
        $this->loginUrl = $loginUrl;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    public function build(): self
    {
        return $this->markdown('emails.delivery.application-approved')
            ->subject('Your Delivery Partner Account Has Been Approved')
            ->with([
                'user' => $this->user,
                'loginUrl' => $this->loginUrl,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
            ]);
    }
}
