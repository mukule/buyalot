<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Storefront queries for campaigns (Today's Deals, Offers, Seasonal).
 */
class CampaignService
{
    public function __construct(
        protected FrontendProductService $productService,
        protected DealPricingService $dealPricing,
    ) {}

    /**
     * Live campaigns flagged for the homepage, each with up to $limit products.
     * Today's Deals come first, then Offers, then Seasonal, each by priority.
     */
    public function homeSections(int $limit = 12): Collection
    {
        $version = Cache::get(DealPricingService::CACHE_VERSION_KEY, 1);

        $campaigns = Cache::remember("campaigns:homepage:v{$version}", now()->addMinutes(2), function () use ($limit) {
            return Campaign::live()
                ->where('show_on_home', true)
                ->orderByRaw("FIELD(type, 'daily_deal', 'offer', 'seasonal')")
                ->orderBy('priority')
                ->limit(6)
                ->get()
                ->map(fn (Campaign $c) => $this->summary($c) + [
                    'product_ids' => $this->productIds($c, $limit),
                ]);
        });

        // One query for every section's products.
        $items = $this->productService
            ->getItemsForProductIds($campaigns->pluck('product_ids')->flatten()->unique()->values()->all())
            ->keyBy('product_id');

        return $campaigns
            ->map(function (array $c) use ($items) {
                $c['products'] = collect($c['product_ids'])->map(fn ($id) => $items->get($id))->filter()->values();
                unset($c['product_ids']);

                return $c;
            })
            ->filter(fn ($c) => $c['products']->isNotEmpty())
            ->values();
    }

    /**
     * Live campaigns (optionally of one type) with a few preview products each.
     */
    public function liveCampaigns(?string $type = null, int $previewLimit = 6): Collection
    {
        $campaigns = Campaign::live()
            ->ofType($type)
            ->withCount(['products' => fn ($q) => $q->withoutGlobalScopes()->where('status_id', 2)])
            ->orderByRaw("FIELD(type, 'daily_deal', 'offer', 'seasonal')")
            ->orderBy('priority')
            ->get();

        $idsByCampaign = $campaigns->mapWithKeys(fn ($c) => [$c->id => $this->productIds($c, $previewLimit)]);
        $items = $this->productService
            ->getItemsForProductIds($idsByCampaign->flatten()->unique()->values()->all())
            ->keyBy('product_id');

        return $campaigns->map(fn (Campaign $c) => $this->summary($c) + [
            'products_count' => $c->products_count,
            'products'       => collect($idsByCampaign[$c->id])->map(fn ($id) => $items->get($id))->filter()->values(),
        ]);
    }

    /**
     * Paginated products for one campaign, or for every live campaign of a type
     * (used by the /deals/today shortcut).
     *
     * @param  Campaign|string  $source  a campaign or a campaign type
     */
    public function paginatedProducts(Campaign|string $source, int $perPage = 30): LengthAwarePaginator
    {
        $query = DB::table('campaign_product')
            ->join('products', 'products.id', '=', 'campaign_product.product_id')
            ->where('products.status_id', 2)
            ->whereNotNull('products.slug')
            ->where('products.slug', '!=', '');

        if ($source instanceof Campaign) {
            $query->where('campaign_product.campaign_id', $source->id);
        } else {
            $liveIds = Campaign::live()->ofType($source)->pluck('id');
            $query->whereIn('campaign_product.campaign_id', $liveIds);
        }

        $ids = $query
            ->groupBy('campaign_product.product_id')
            ->orderByRaw('MIN(campaign_product.sort_order)')
            ->orderBy('campaign_product.product_id')
            ->pluck('campaign_product.product_id');

        $page  = Paginator::resolveCurrentPage();
        $slice = $ids->forPage($page, $perPage)->values()->all();

        return new LengthAwarePaginator(
            $this->productService->getItemsForProductIds($slice),
            $ids->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Earliest end time among live campaigns of a type (for the countdown).
     */
    public function endsAtForType(string $type): ?string
    {
        return Campaign::live()->ofType($type)->whereNotNull('ends_at')->min('ends_at');
    }

    public function summary(Campaign $c): array
    {
        return [
            'id'               => $c->id,
            'title'            => $c->title,
            'slug'             => $c->slug,
            'type'             => $c->type,
            'type_label'       => Campaign::TYPES[$c->type] ?? $c->type,
            'subtitle'         => $c->subtitle,
            'description'      => $c->description,
            'banner_url'       => $c->banner_url,
            'theme_color'      => $c->theme_color,
            'discount_percent' => $c->discount_percent,
            'starts_at'        => $c->starts_at?->toIso8601String(),
            'ends_at'          => $c->ends_at?->toIso8601String(),
        ];
    }

    protected function productIds(Campaign $campaign, int $limit): array
    {
        return DB::table('campaign_product')
            ->join('products', 'products.id', '=', 'campaign_product.product_id')
            ->where('campaign_product.campaign_id', $campaign->id)
            ->where('products.status_id', 2)
            ->orderBy('campaign_product.sort_order')
            ->orderBy('campaign_product.id')
            ->limit($limit)
            ->pluck('campaign_product.product_id')
            ->all();
    }
}
