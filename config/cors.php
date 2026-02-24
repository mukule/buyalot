<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The POS frontend runs on its own subdomain (e.g. pos.buyalot.com)
    | and needs cross-origin access to the /api/pos/* and /payments/* endpoints.
    |
    */

    'paths' => ['api/*', 'payments/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('POS_APP_URL', 'http://localhost:5174'),
        env('APP_URL'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
