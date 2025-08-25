<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPreferences;
use Illuminate\Http\Request;

class CustomerPreferencesController extends Controller
{
    public function show(Customer $customer)
    {
        $preferences = $customer->preferences ?? new CustomerPreferences();
        return view('customers.preferences.show', compact('customer', 'preferences'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'newsletter' => 'boolean',
            'order_updates' => 'boolean',
            'promotion_alerts' => 'boolean',
            'review_reminders' => 'boolean',
            'abandoned_cart_reminders' => 'boolean',
            'wishlist_notifications' => 'boolean',
            'price_drop_alerts' => 'boolean',
            'back_in_stock_alerts' => 'boolean',
            'birthday_offers' => 'boolean',
            'preferred_communication_time' => 'nullable|string',
            'language' => 'required|string|max:5',
            'currency' => 'required|string|max:3',
            'timezone' => 'required|string',
            'theme' => 'nullable|string',
            'email_frequency' => 'nullable|string',
            'categories_of_interest' => 'nullable|array',
        ]);

        $customer->preferences()->updateOrCreate(
            ['customer_id' => $customer->id],
            $validated
        );

        return back()->with('success', 'Preferences updated successfully.');
    }
}
