<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\SellerApplication;

class NewSellerApplicationRegisteredAdmin extends Notification
{
    use Queueable;

    protected $application;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Seller Application Submitted')
            ->greeting('Hello Admin,')
            ->line('A new seller application has been submitted.')
            ->line('Applicant: ' . $this->application->first_name . ' ' . $this->application->last_name)
            ->line('Email: ' . $this->application->contact_email)
            ->action('View Application', url(route('admin.seller-applications.show', $this->application->id)))
            ->line('Please review the application at your earliest convenience.');
    }
}
