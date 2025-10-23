<?php

namespace App\Listeners;

use App\Events\CustomerRegistered;
use App\Mail\WelcomeCustomerMail;
use App\Models\Customer\CustomerSegment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerRegistered $event): void
    {
        Mail::to($event->customer->email)->send(new WelcomeCustomerMail($event->customer));

        // Add to default customer segment
        $defaultSegment = CustomerSegment::where('name', 'New Customer')->first();
        if ($defaultSegment) {
            $event->customer->segments()->attach($defaultSegment, [
                'assigned_at' => now(),
            ]);
        }

        // Create initial marketing preferences if not exists
        if (!$event->customer->marketingPreferences) {
            $event->customer->marketingPreferences()->create([
                'accepts_marketing' => true,
                'accepts_sms' => false,
                'accepts_phone_calls' => false,
                'accepts_push_notifications' => true,
            ]);
        }
        if (!$event->customer->statistics) {
            $event->customer->statistics()->create([
                'total_orders' => 0,
                'total_spent' => 0,
                'average_order_value' => 0,
                'customer_lifetime_value' => 0,
            ]);
        }
    }
}
