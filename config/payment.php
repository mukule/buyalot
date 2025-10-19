<?php

return [
    // General payment config
    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'KES'),
    'expiry_minutes' => (int) env('PAYMENT_EXPIRY_MINUTES', 15),

    // Providers configuration
    'providers' => [
        'mpesa' => [
            'enabled' => true,

            // Credentials
            'consumer_key' => env('MPESA_CONSUMER_KEY'),
            'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
            'business_short_code' => env('MPESA_BUSINESS_SHORT_CODE'),
            'passkey' => env('MPESA_PASSKEY'),
            'transaction_desc' => env('MPESA_TRANSACTION_DESC', 'Payment for Order'),

            // URLs
            'base_url' => env('MPESA_BASE_URL'),
            'auth_url' => env('MPESA_AUTH_URL'),
            'stk_push_url' => env('MPESA_STK_PUSH_URL'),
            'query_url' => env('MPESA_QUERY_URL'),
            'callback_url' => env('MPESA_CALLBACK_URL'),
        ],
    ],
];
