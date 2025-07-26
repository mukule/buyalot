<?php

namespace App\Mail;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerVerificationStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public SellerApplication $application;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = $this->application->verified
            ? 'Your Application Has Been Verified'
            : 'Your Application Verification Has Been Revoked';

        return $this->subject($subject)
                    ->markdown('emails.seller.verification-status')
                    ->with([
                        'app' => $this->application,
                    ]);
    }
}
