<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosSetting extends Model
{
    protected $fillable = [
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'tax_number',
        'vat_percentage',
        'vat_enabled',
        'require_admin_void',
        'show_product_images',
        'product_display_design',
        'currency_symbol',
        'receipt_header',
        'receipt_footer',
        'invoice_prefix',
        'receipt_prefix',
        'payment_methods',
        'metadata',
        'id',
        'name',
        'enabled',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'require_admin_void' => 'boolean',
        'show_product_images' => 'boolean',
        'vat_percentage' => 'decimal:2',
        'payment_methods' => 'json',
        'metadata' => 'json',
    ];

    public static function getSettings()
    {
        $settings = self::first();
        if (!$settings) {
            $settings = self::create([
                'business_name' => config('app.name'),
                'currency_symbol' => 'KES',
                'invoice_prefix' => 'INV-',
                'receipt_prefix' => 'RCPT-',
                'payment_methods' => [
                    ['id' => 'cash', 'name' => 'Cash', 'enabled' => true],
                    ['id' => 'mpesa', 'name' => 'M-Pesa', 'enabled' => true],
                ],
            ]);
        }
        return $settings;
    }
}
