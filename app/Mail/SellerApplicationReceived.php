<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerApplication;

class SellerApplicationReceived extends Mailable
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
        return $this->markdown('emails.seller.application_received')
                    ->subject('Your Seller Application Has Been Received')
                    ->with([
                        'application' => $this->application,
                        'logoUrl' => $this->logoUrl,
                        'appName' => $this->appName,
                    ]);
    }
}
