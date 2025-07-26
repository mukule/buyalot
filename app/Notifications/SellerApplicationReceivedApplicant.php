<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\SellerApplication;

class SellerApplicationReceivedApplicant extends Notification
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
            ->subject('Your Seller Application Has Been Received')
            ->greeting('Hello ' . $this->application->first_name . ',')
            ->line('Thank you for submitting your seller application.')
            ->line('We have received your application and will review it shortly.')
            ->line('If you have any questions, feel free to reply to this email.')
            ->line('Best regards,')
            ->line(config('app.name'));
    }
}
