<?php

return [
    'default_payment_terms_days' => env('COMMISSION_PAYMENT_TERMS', 30),
    'auto_generate_invoices' => env('COMMISSION_AUTO_GENERATE_INVOICES', true),
    'invoice_prefix' => env('COMMISSION_INVOICE_PREFIX', 'INV'),
    'currency' => env('COMMISSION_CURRENCY', 'USD'),
    'currency_symbol' => env('COMMISSION_CURRENCY_SYMBOL', '
            '),
    'tax_rate' => env('COMMISSION_TAX_RATE', 0),
    'minimum_payout' => env('COMMISSION_MINIMUM_PAYOUT', 10),

    'calculation_types' => [
        'percentage' => 'Percentage of Sale',
        'fixed_per_item' => 'Fixed Amount Per Item',
        'fixed_per_order' => 'Fixed Amount Per Order',
        'tiered' => 'Tiered Commission',
    ],

    'condition_operators' => [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'greater_than' => 'Greater Than',
        'less_than' => 'Less Than',
        'greater_than_or_equal' => 'Greater Than or Equal',
        'less_than_or_equal' => 'Less Than or Equal',
        'in' => 'In List',
        'not_in' => 'Not In List',
        'contains' => 'Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
        'between' => 'Between',
    ],

    'billing_cycles' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annually' => 'Annually',
    ],
];
