<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerDocument;

class SellerDocumentReviewNotification extends Mailable
{
    use Queueable, SerializesModels;

    public SellerDocument $sellerDocument;
    public string $appName;
    public string $logoUrl;

    public function __construct(SellerDocument $sellerDocument)
    {
        $this->sellerDocument = $sellerDocument;
        $this->appName = config('app.name');
        $this->logoUrl = asset('images/logo.png');
    }

    public function build()
    {
        return $this->markdown('emails.seller.document_review_notification')
            ->subject('Your Document Verification Result')
            ->with([
                'documentTypeName' => $this->sellerDocument->documentType->name,
                'status' => $this->sellerDocument->status,
                'rejectionReason' => $this->sellerDocument->rejection_reason,
                'appName' => $this->appName,
                'logoUrl' => $this->logoUrl,
                'userName' => $this->sellerDocument->user->name ?? 'Applicant',
            ]);
    }
}
