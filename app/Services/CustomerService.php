<?php

namespace App\Services;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPreferences;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerService
{
    public function createCustomer(array $data): Customer
    {
        $customer = Customer::create(array_merge($data, [
            'customer_since' => now(),
            'status' => 'active'
        ]));
        $this->createDefaultPreferences($customer);

        return $customer;
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function deleteCustomer(Customer $customer): void
    {
        // Soft delete to maintain referential integrity
        $customer->update(['status' => 'deleted']);
        $customer->delete();
    }

    public function getCustomerStats(Customer $customer): array
    {
        return [
            'total_orders' => $customer->getOrderCount(),
            'total_spent' => $customer->getTotalSpent(),
            'loyalty_points' => $customer->getTotalLoyaltyPoints(),
            'addresses_count' => $customer->addresses()->count(),
            'wishlist_count' => $customer->wishlistItems()->count(),
            'support_tickets_count' => $customer->supportTickets()->count(),
            'open_tickets_count' => $customer->supportTickets()->open()->count(),
            'successful_referrals' => $customer->referrals()->successful()->count(),
            'average_order_value' => $customer->orders()->avg('total') ?? 0,
            'last_order_date' => $customer->orders()->latest()->first()?->created_at,
        ];
    }

    public function getDashboardStats(Customer $customer): array
    {
        $stats = $this->getCustomerStats($customer);

        return array_merge($stats, [
            'recent_orders' => $customer->orders()->with('items')->latest()->limit(5)->get(),
            'recent_points' => $customer->loyaltyPoints()->latest()->limit(10)->get(),
            'active_wishlist' => $customer->wishlistItems()->with('wishlistable')->limit(10)->get(),
        ]);
    }

    private function createDefaultPreferences(Customer $customer): CustomerPreferences
    {
        return CustomerPreferences::create([
            'customer_id' => $customer->id,
            'email_notifications' => true,
            'order_updates' => true,
            'newsletter' => false,
            'marketing_emails' => false,
            'language' => 'en',
            'currency' => 'USD',
            'timezone' => 'UTC',
        ]);
    }

}
