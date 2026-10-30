<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\CampaignService;
use App\Services\Seo\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Storefront pages for Today's Deals, Offers and Seasonal promotions.
 */
class DealsController extends Controller
{
    public function __construct(protected CampaignService $campaigns) {}

    /**
     * /deals — every live campaign, optionally filtered by ?type=.
     */
    public function index(Request $request)
    {
        $type = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(Campaign::TYPES))],
        ])['type'] ?? null;

        $title = $type ? Campaign::TYPES[$type] : 'Deals & Offers';

        return Inertia::render('Frontend/Deals/Index', [
            'title'     => $title,
            'seo'       => SeoMeta::deals($title, '/deals' . ($type ? "?type={$type}" : '')),
            'type'      => $type,
            'types'     => Campaign::TYPES,
            'campaigns' => $this->campaigns->liveCampaigns($type),
        ]);
    }

    /**
     * /deals/today — products from every live "Today's Deals" campaign.
     */
    public function today()
    {
        $title = Campaign::TYPES[Campaign::TYPE_DAILY_DEAL];

        return Inertia::render('Frontend/Deals/Show', [
            'title'    => $title,
            'seo'      => SeoMeta::deals($title, '/deals/today'),
            'campaign' => [
                'title'       => $title,
                'slug'        => 'today',
                'type'        => Campaign::TYPE_DAILY_DEAL,
                'type_label'  => $title,
                'subtitle'    => 'Fresh discounts every day — grab them before the clock runs out.',
                'description' => null,
                'banner_url'  => null,
                'theme_color' => null,
                'ends_at'     => optional($this->campaigns->endsAtForType(Campaign::TYPE_DAILY_DEAL),
                    fn ($d) => \Illuminate\Support\Carbon::parse($d)->toIso8601String()),
            ],
            'products' => $this->campaigns->paginatedProducts(Campaign::TYPE_DAILY_DEAL),
        ]);
    }

    /**
     * /deals/{slug} — one campaign.
     */
    public function show(string $slug)
    {
        $campaign = Campaign::live()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Frontend/Deals/Show', [
            'title'    => $campaign->title,
            'seo'      => SeoMeta::deals($campaign->title, "/deals/{$campaign->slug}", $campaign->description ?? $campaign->subtitle, $campaign->banner_url),
            'campaign' => $this->campaigns->summary($campaign),
            'products' => $this->campaigns->paginatedProducts($campaign),
        ]);
    }
}
