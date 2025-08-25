<?php


return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'KES'),

    /*
    |--------------------------------------------------------------------------
    | Payment Expiry (in minutes)
    |--------------------------------------------------------------------------
    */
'expiry_minutes' => env('PAYMENT_EXPIRY_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Payment Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
    'mpesa' => [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'business_short_code' => env('MPESA_BUSINESS_SHORT_CODE'),
    'passkey' => env('MPESA_PASSKEY'),
    'transaction_desc' => env('MPESA_TRANSACTION_DESC', 'Payment'),

        // URLs
    'base_url' => env('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    'auth_url' => env('MPESA_AUTH_URL'),
    'stk_push_url' => env('MPESA_STK_PUSH_URL'),
    'query_url' => env('MPESA_QUERY_URL'),
    'callback_url' => env('MPESA_CALLBACK_URL', '/api/payments/callback/mpesa'),

        // Environment
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // sandbox or live
    ],

    'stripe' => [
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // TODO Add other providers here
],
];

