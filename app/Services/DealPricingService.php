<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the campaign deal (if any) for each product and applies it on top of
 * the variant selling price. This is the single source of truth used by the
 * storefront listings, product page, cart and checkout, so a customer is always
 * charged the price they were shown.
 *
 * Rule: a product in several live campaigns gets the largest percentage.
 */
class DealPricingService
{
    public const CACHE_VERSION_KEY = 'campaign_deals_version';

    /** Per-request memo so repeated lookups don't hit the cache store. */
    protected ?array $deals = null;

    /**
     * product_id => ['percent' => float, 'ends_at' => ?string, 'campaign' => string]
     */
    public function liveDeals(): array
    {
        if ($this->deals !== null) {
            return $this->deals;
        }

        $version = Cache::get(self::CACHE_VERSION_KEY, 1);

        // Short TTL so campaigns that start/end on schedule take effect quickly.
        return $this->deals = Cache::remember("campaign_deals_v{$version}", now()->addMinutes(2), function () {
            $rows = DB::table('campaign_product')
                ->join('campaigns', 'campaigns.id', '=', 'campaign_product.campaign_id')
                ->where('campaigns.is_active', true)
                ->where(fn ($q) => $q->whereNull('campaigns.starts_at')->orWhere('campaigns.starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('campaigns.ends_at')->orWhere('campaigns.ends_at', '>=', now()))
                ->select([
                    'campaign_product.product_id',
                    'campaigns.slug',
                    'campaigns.ends_at',
                    DB::raw('COALESCE(campaign_product.discount_percent, campaigns.discount_percent) as percent'),
                ])
                ->get();

            $deals = [];
            foreach ($rows as $row) {
                $percent = (float) $row->percent;
                if ($percent <= 0) continue;

                $current = $deals[$row->product_id] ?? null;
                if (! $current || $percent > $current['percent']) {
                    $deals[$row->product_id] = [
                        'percent'  => min($percent, 100),
                        'ends_at'  => $row->ends_at,
                        'campaign' => $row->slug,
                    ];
                }
            }

            return $deals;
        });
    }

    public function dealFor(?int $productId): ?array
    {
        return $productId ? ($this->liveDeals()[$productId] ?? null) : null;
    }

    /**
     * Price a single variant. Returns null when no deal applies.
     *
     * @return array{marked_price: float, final_price: float, total_discount: float, discount_percent: float, deal: array}|null
     */
    public function priceFor(?int $productId, float $sellingPrice, float $markedPrice): ?array
    {
        $deal = $this->dealFor($productId);
        if (! $deal || $sellingPrice <= 0) {
            return null;
        }

        $final  = round($sellingPrice * (1 - $deal['percent'] / 100), 2);
        // Strike-through price: the regular marked price, or the pre-deal selling price.
        $marked = round(max($markedPrice, $sellingPrice), 2);
        $total  = round(max($marked - $final, 0), 2);

        return [
            'marked_price'     => $marked,
            'final_price'      => $final,
            'total_discount'   => $total,
            'discount_percent' => $marked > 0 ? round(($total / $marked) * 100, 2) : 0,
            'deal'             => $deal,
        ];
    }

    /**
     * Apply deals to storefront items already shaped by
     * FrontendProductService::normalizeVariant (needs product_id, marked_price, final_price).
     * Runs after any caching so deal prices are never stale.
     */
    public function applyToItem(array $item): array
    {
        $priced = $this->priceFor(
            $item['product_id'] ?? null,
            (float) ($item['final_price'] ?? 0),
            (float) ($item['marked_price'] ?? 0),
        );

        if (! $priced) {
            return $item;
        }

        return array_merge($item, [
            'marked_price'     => $priced['marked_price'],
            'final_price'      => $priced['final_price'],
            'discount_percent' => $priced['discount_percent'],
            'has_discount'     => $priced['total_discount'] > 0,
            'deal_percent'     => $priced['deal']['percent'],
            'deal_ends_at'     => $priced['deal']['ends_at'],
        ]);
    }

    public function applyToItems(iterable $items)
    {
        return collect($items)->map(fn ($item) => is_array($item) ? $this->applyToItem($item) : $item)->values();
    }

    /**
     * Invalidate deal prices and the homepage product cache after a campaign change.
     */
    public function flush(): void
    {
        $this->deals = null;
        Cache::forever(self::CACHE_VERSION_KEY, (int) Cache::get(self::CACHE_VERSION_KEY, 1) + 1);
        Cache::forget('campaigns:homepage');
    }
}
