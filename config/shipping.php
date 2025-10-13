<?php

return [
    // Base fee applied to every shipment
    'base_fee' => env('SHIPPING_BASE_FEE', 50), // KES

    // Rate per kilometer regardless of items
    'per_km' => env('SHIPPING_PER_KM', 12), // KES per km

    // Additional fee per item, helps reflect handling
    'per_item' => env('SHIPPING_PER_ITEM', 10), // KES per item

    // Optional minimum and maximum caps for total shipping
    'min_total' => env('SHIPPING_MIN_TOTAL', 0),
    'max_total' => env('SHIPPING_MAX_TOTAL', 0), // 0 = no cap
];
