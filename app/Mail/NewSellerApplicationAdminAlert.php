<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerApplication;

class NewSellerApplicationAdminAlert extends Mailable
{
    use Queueable, SerializesModels;

    public SellerApplication $application;
    public string $logoUrl;
    public string $appName;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    public function build()
    {
        return $this->markdown('emails.seller.admin_alert')
                    ->subject('New Seller Application Received')
                    ->with([
                        'application' => $this->application,
                        'logoUrl' => $this->logoUrl,
                        'appName' => $this->appName,
                    ]);
    }
}
