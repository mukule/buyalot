<?php

namespace App\Mail;

use App\Models\DeliveryPersonApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryApplicationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public DeliveryPersonApplication $application;
    public string $reason;
    public string $logoUrl;
    public string $appName;

    public function __construct(DeliveryPersonApplication $application, string $reason)
    {
        $this->application = $application;
        $this->reason = $reason;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    public function build(): self
    {
        return $this->markdown('emails.delivery.application-rejected')
            ->subject('Your Delivery Partner Application Was Not Approved')
            ->with([
                'application' => $this->application,
                'reason' => $this->reason,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
            ]);
    }
}
