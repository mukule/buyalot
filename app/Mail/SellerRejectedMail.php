<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerApplication;

class SellerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public SellerApplication $application;
    public string $reason;
    public string $logoUrl;
    public string $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(SellerApplication $application, string $reason)
    {
        $this->application = $application;
        $this->reason = $reason;
        $this->logoUrl = asset('images/logo.png');  
        $this->appName = config('app.name');
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.seller.seller-rejected')
            ->subject('Your Seller Application Has been Closed')
            ->with([
                'application' => $this->application,
                'reason' => $this->reason,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
            ]);
    }
}
