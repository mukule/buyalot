<?php

namespace App\Models\Customer;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPreferences extends Model
{
    use HasFactory,HasHashid;

    protected $fillable = [
        'customer_id', 'email_notifications', 'sms_notifications',
        'push_notifications', 'marketing_emails', 'newsletter',
        'order_updates', 'promotion_alerts', 'review_reminders',
        'abandoned_cart_reminders', 'wishlist_notifications',
        'price_drop_alerts', 'back_in_stock_alerts', 'birthday_offers',
        'preferred_communication_time', 'language', 'currency',
        'timezone', 'theme', 'email_frequency', 'categories_of_interest'
    ];

    protected $casts = [
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
        'categories_of_interest' => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Methods
    public function allowsEmailNotifications(): bool
    {
        return $this->email_notifications;
    }

    public function allowsMarketingEmails(): bool
    {
        return $this->marketing_emails;
    }

    public function isInterestedInCategory(string $category): bool
    {
        return in_array($category, $this->categories_of_interest ?? []);
    }
}
