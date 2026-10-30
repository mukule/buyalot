<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Products\Product;
use App\Services\DealPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Admin management of Today's Deals / Offers / Seasonal campaigns and the
 * products attached to them.
 */
class CampaignController extends Controller
{
    public function __construct(protected DealPricingService $dealPricing) {}

    public function index(Request $request)
    {
        $campaigns = Campaign::query()
            ->withCount('products')
            ->ofType($request->input('type'))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Campaign $c) => $this->present($c) + ['products_count' => $c->products_count]);

        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $campaigns,
            'types'     => Campaign::TYPES,
            'filters'   => $request->only(['type', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('campaigns', 'public');
        }

        $campaign = Campaign::create($data);
        $this->dealPricing->flush();

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign created. Now add products to it.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request, $campaign);

        if ($request->hasFile('banner_image')) {
            if ($campaign->banner_image) {
                Storage::disk('public')->delete($campaign->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('campaigns', 'public');
        } elseif ($request->boolean('remove_banner') && $campaign->banner_image) {
            Storage::disk('public')->delete($campaign->banner_image);
            $data['banner_image'] = null;
        }

        $campaign->update($data);
        $this->dealPricing->flush();

        return redirect()->back()->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->banner_image) {
            Storage::disk('public')->delete($campaign->banner_image);
        }

        $campaign->delete();
        $this->dealPricing->flush();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    /**
     * Manage the products in a campaign.
     */
    public function show(Request $request, Campaign $campaign)
    {
        $products = $campaign->products()
            ->withoutGlobalScopes()
            ->with('primaryImage')
            ->withMin('productVariants', 'selling_price')
            ->when($request->filled('search'), fn ($q) => $q->where('products.name', 'like', '%' . $request->search . '%'))
            ->orderBy('campaign_product.sort_order')
            ->orderBy('campaign_product.id')
            ->paginate(25)
            ->withQueryString()
            ->through(function (Product $p) use ($campaign) {
                $price   = (float) $p->product_variants_min_selling_price;
                $percent = $p->pivot->discount_percent ?? $campaign->discount_percent;

                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'slug'             => $p->slug,
                    'image_url'        => $p->primary_image_url,
                    'is_published'     => (int) $p->status_id === 2,
                    'price'            => $price,
                    'discount_percent' => $p->pivot->discount_percent !== null ? (float) $p->pivot->discount_percent : null,
                    'effective_percent'=> $percent !== null ? (float) $percent : null,
                    'deal_price'       => $percent ? round($price * (1 - $percent / 100), 2) : $price,
                    'sort_order'       => (int) $p->pivot->sort_order,
                ];
            });

        return Inertia::render('Admin/Campaigns/Products', [
            'campaign'   => $this->present($campaign) + ['products_count' => $campaign->products()->count()],
            'products'   => $products,
            'types'      => Campaign::TYPES,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'parent_id']),
            'filters'    => $request->only(['search']),
        ]);
    }

    /**
     * Product autocomplete for the "add products" panel; excludes products already in the campaign.
     */
    public function searchProducts(Request $request, Campaign $campaign)
    {
        $term = trim((string) $request->input('query', ''));
        $categoryId = $request->integer('category_id') ?: null;

        if (strlen($term) < 2 && ! $categoryId) {
            return response()->json([]);
        }

        $categoryIds = $categoryId
            ? Category::find($categoryId)?->getAllCategoryIds()->all() ?? []
            : null;

        $products = Product::query()
            ->withoutGlobalScopes()
            ->where('status_id', 2)
            ->whereNotIn('id', DB::table('campaign_product')->where('campaign_id', $campaign->id)->select('product_id'))
            ->when(strlen($term) >= 2, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('product_code', 'like', "%{$term}%")))
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->with('primaryImage')
            ->withMin('productVariants', 'selling_price')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($products->map(fn (Product $p) => [
            'id'        => $p->id,
            'name'      => $p->name,
            'image_url' => $p->primary_image_url,
            'price'     => (float) $p->product_variants_min_selling_price,
        ]));
    }

    /**
     * Attach products — either picked individually or every published product in a category.
     */
    public function addProducts(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'product_ids'      => ['required_without:category_id', 'array'],
            'product_ids.*'    => ['integer', 'exists:products,id'],
            'category_id'      => ['nullable', 'integer', 'exists:categories,id'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $ids = collect($data['product_ids'] ?? []);

        if (! empty($data['category_id'])) {
            $categoryIds = Category::findOrFail($data['category_id'])->getAllCategoryIds()->all();
            $ids = $ids->merge(
                Product::withoutGlobalScopes()
                    ->whereIn('category_id', $categoryIds)
                    ->where('status_id', 2)
                    ->pluck('id')
            );
        }

        $existing = DB::table('campaign_product')->where('campaign_id', $campaign->id)->pluck('product_id');
        $newIds   = $ids->unique()->diff($existing)->values();
        $nextSort = (int) DB::table('campaign_product')->where('campaign_id', $campaign->id)->max('sort_order') + 1;

        $campaign->products()->attach(
            $newIds->mapWithKeys(fn ($id, $i) => [$id => [
                'discount_percent' => $data['discount_percent'] ?? null,
                'sort_order'       => $nextSort + $i,
            ]])->all()
        );

        $this->dealPricing->flush();

        return redirect()->back()->with('success', $newIds->count() . ' product(s) added to the campaign.');
    }

    /**
     * Change one product's discount override or display order.
     */
    public function updateProduct(Request $request, Campaign $campaign, int $productId)
    {
        $data = $request->validate([
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $pivot = DB::table('campaign_product')
            ->where('campaign_id', $campaign->id)
            ->where('product_id', $productId);

        abort_unless($pivot->exists(), 404);

        $changes = ['updated_at' => now()];
        if ($request->has('discount_percent')) {
            // Empty value clears the override so the campaign default applies.
            $changes['discount_percent'] = $data['discount_percent'];
        }
        if (isset($data['sort_order'])) {
            $changes['sort_order'] = $data['sort_order'];
        }

        $pivot->update($changes);
        $this->dealPricing->flush();

        return redirect()->back()->with('success', 'Product updated.');
    }

    public function removeProducts(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'product_ids'   => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer'],
        ]);

        $removed = $campaign->products()->detach($data['product_ids']);
        $this->dealPricing->flush();

        return redirect()->back()->with('success', "{$removed} product(s) removed from the campaign.");
    }

    protected function validated(Request $request, ?Campaign $campaign = null): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::notIn(Campaign::RESERVED_SLUGS),
                Rule::unique('campaigns', 'slug')->ignore($campaign?->id),
            ],
            'type'             => ['required', Rule::in(array_keys(Campaign::TYPES))],
            'subtitle'         => ['nullable', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'banner_image'     => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'theme_color'      => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'starts_at'        => ['nullable', 'date'],
            'ends_at'          => ['nullable', 'date', 'after:starts_at'],
            'is_active'        => ['boolean'],
            'show_on_home'     => ['boolean'],
            'priority'         => ['nullable', 'integer', 'min:0'],
        ]);

        unset($data['banner_image']);
        $data['priority'] = $data['priority'] ?? 0;

        // Today's Deals without an end date default to midnight so the countdown is meaningful.
        if ($data['type'] === Campaign::TYPE_DAILY_DEAL && empty($data['ends_at'])) {
            $data['ends_at'] = ($data['starts_at'] ?? null)
                ? \Illuminate\Support\Carbon::parse($data['starts_at'])->endOfDay()
                : now()->endOfDay();
        }

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Campaign::uniqueSlug($data['title'], $campaign?->id);
        }

        return $data;
    }

    protected function present(Campaign $c): array
    {
        return [
            'id'               => $c->id,
            'title'            => $c->title,
            'slug'             => $c->slug,
            'type'             => $c->type,
            'subtitle'         => $c->subtitle,
            'description'      => $c->description,
            'banner_url'       => $c->banner_url,
            'theme_color'      => $c->theme_color,
            'discount_percent' => $c->discount_percent,
            'starts_at'        => $c->starts_at?->format('Y-m-d\TH:i'),
            'ends_at'          => $c->ends_at?->format('Y-m-d\TH:i'),
            'is_active'        => $c->is_active,
            'show_on_home'     => $c->show_on_home,
            'priority'         => $c->priority,
            'status'           => $c->status,
        ];
    }
}
