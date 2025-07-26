<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerApplication;
use Illuminate\Support\Collection;

class SellerDocumentsSubmittedAdminAlert extends Mailable
{
    use Queueable, SerializesModels;

    public SellerApplication $application;
    public Collection $submittedDocuments;
    public string $logoUrl;
    public string $appName;

    /**
     * @param SellerApplication $application
     * @param Collection $submittedDocuments Collection of SellerDocument models
     */
    public function __construct(SellerApplication $application, Collection $submittedDocuments)
    {
        $this->application = $application;
        $this->submittedDocuments = $submittedDocuments;
        $this->logoUrl = asset('images/logo.png');
        $this->appName = config('app.name');
    }

    public function build()
    {
        return $this->markdown('emails.seller.documents_submitted')
                    ->subject('Seller Document Submission')
                    ->with([
                        'application' => $this->application,
                        'submittedDocuments' => $this->submittedDocuments,
                        'logoUrl' => $this->logoUrl,
                        'appName' => $this->appName,
                    ]);
    }
}
