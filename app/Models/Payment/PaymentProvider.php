<?php

namespace App\Models\Payment;

enum PaymentProvider: string
{
    case MPESA = 'mpesa';
    case AIRTELMONEY = 'airtelmoney';
    case MASTERCARD = 'mastercard';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case FLUTTERWAVE = 'flutterwave';
    case CASH_ON_DELIVERY = 'cash_on_delivery';

    public function label(): string
    {
        return match($this) {
            self::MPESA => 'M-Pesa',
            self::AIRTELMONEY => 'Airtel Money',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::FLUTTERWAVE => 'Flutterwave',
            self::CASH_ON_DELIVERY => 'Cash on Delivery',
        };
    }
}

