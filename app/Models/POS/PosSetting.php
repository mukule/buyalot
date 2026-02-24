<?php

namespace App\Models\POS;

use App\Models\Seller\Seller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSetting extends Model
{
    protected $fillable = [
        'seller_id',
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'tax_number',
        'vat_percentage',
        'vat_enabled',
        'require_admin_void',
        'show_product_images',
        'max_tabs',
        'product_display_design',
        'currency_symbol',
        'receipt_header',
        'receipt_footer',
        'invoice_prefix',
        'receipt_prefix',
        'payment_methods',
        'metadata',
        'settings_version',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'require_admin_void' => 'boolean',
        'show_product_images' => 'boolean',
        'vat_percentage' => 'decimal:2',
        'max_tabs' => 'integer',
        'settings_version' => 'integer',
        'payment_methods' => 'json',
        'metadata' => 'json',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Get the global (system-wide) settings. Creates defaults if none exist.
     */
    public static function getSettings(): self
    {
        $settings = self::whereNull('seller_id')->first();

        if (! $settings) {
            $settings = self::create([
                'seller_id' => null,
                'business_name' => config('app.name'),
                'currency_symbol' => 'KES',
                'invoice_prefix' => 'INV-',
                'receipt_prefix' => 'RCPT-',
                'max_tabs' => 1,
                'show_product_images' => true,
                'payment_methods' => [
                    ['id' => 'cash', 'name' => 'Cash', 'enabled' => true],
                    ['id' => 'mpesa', 'name' => 'M-Pesa', 'enabled' => true],
                ],
            ]);
        }

        return $settings;
    }

    /**
     * Get the per-seller settings row, creating it from global defaults if absent.
     */
    public static function getForSeller(int $sellerId): self
    {
        $seller = self::where('seller_id', $sellerId)->first();

        if ($seller) {
            return $seller;
        }

        $global = self::getSettings();

        return self::create(
            collect($global->toArray())
                ->except(['id', 'created_at', 'updated_at'])
                ->merge(['seller_id' => $sellerId, 'settings_version' => 1])
                ->toArray()
        );
    }

    /**
     * Resolve the effective settings for a seller.
     * Returns the seller-specific row if one exists, otherwise the global row.
     */
    public static function resolveForSeller(?int $sellerId): self
    {
        if ($sellerId) {
            $seller = self::where('seller_id', $sellerId)->first();
            if ($seller) {
                return $seller;
            }
        }

        return self::getSettings();
    }

    /**
     * Bump the version counter so frontends know to refetch.
     */
    public function bumpVersion(): void
    {
        $this->increment('settings_version');
    }
}
