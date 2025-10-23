<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMarketingPreferences extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'accepts_marketing', 'accepts_sms', 'accepts_phone_calls',
        'accepts_push_notifications', 'channel_preferences', 'frequency_preferences',
        'content_preferences'
    ];

    protected $casts = [
        'accepts_marketing' => 'boolean',
        'accepts_sms' => 'boolean',
        'accepts_phone_calls' => 'boolean',
        'accepts_push_notifications' => 'boolean',
        'channel_preferences' => 'array',
        'frequency_preferences' => 'array',
        'content_preferences' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
