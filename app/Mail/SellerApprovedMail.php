<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $password;
    public string $loginUrl;
    public string $logoUrl;
    public string $appName;

    public function __construct(User $user, string $password, string $loginUrl)
    {
        $this->user = $user;
        $this->password = $password;
        $this->loginUrl = $loginUrl;
        $this->logoUrl = asset('images/logo.png'); // Adjust path if needed
        $this->appName = config('app.name');
    }

    public function build()
    {
        return $this->markdown('emails.seller.seller-approved')
                    ->subject('Your Seller Account Has Been Approved')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                        'loginUrl' => $this->loginUrl,
                        'logoUrl' => $this->logoUrl,
                        'appName' => $this->appName,
                    ]);
    }
}
