<?php

namespace App\Notifications;

use App\Models\Customer\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerRegistrationPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Customer $customer, protected string $activationUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your customer account')
            ->greeting('Hi ' . ($this->customer->first_name ?? 'there') . ',')
            ->line('You requested to add a customer account to your existing seller profile.')
            ->line('Click the button below to activate your customer account.')
            ->action('Activate Customer Account', $this->activationUrl)
            ->line('If you did not make this request, please ignore this email.');
    }
}
