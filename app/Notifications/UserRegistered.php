<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegistered extends Notification
{
    use Queueable;

    protected string $appName;

    public function __construct()
    {
        $this->appName = config('app.name');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

   
    public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject("Welcome to {$this->appName}!")
        ->markdown('emails.user.registered', [
            'user' => $notifiable,
            'appName' => $this->appName,
            'logoUrl' => url('/buyalot-02.svg'), 
        ]);
}


    public function toArray(object $notifiable): array
    {
        return [];
    }
}
