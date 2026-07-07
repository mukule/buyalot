<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Products\Product;
use App\Models\Products\ProductQuoteRequest;
use App\Services\FrontendProductService;
use App\Services\PlateDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class MarketplaceController extends Controller
{
    protected FrontendProductService $productService;

    public function __construct(FrontendProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Vertical picker landing page.
     */
    public function index(Request $request)
    {
        return Inertia::render('Frontend/Marketplace/Index', [
            'verticals' => $this->publicVerticals(),
            'active'    => $request->cookie('marketplace_vertical', config('marketplace.default')),
            'title'     => 'Choose a marketplace',
        ]);
    }

    /**
     * Persist the chosen vertical (cookie) and redirect into it.
     */
    public function select(Request $request)
    {
        $key = (string) $request->input('vertical');
        $verticals = config('marketplace.verticals', []);

        if (! isset($verticals[$key])) {
            return back()->with('error', 'Unknown marketplace.');
        }

        // 1 year (in minutes). Plain cookie — no sensitive data.
        Cookie::queue('marketplace_vertical', $key, 525600);

        if (($verticals[$key]['type'] ?? 'ecommerce') === 'ecommerce') {
            return redirect()->route('home');
        }

        return redirect()->route('marketplace.show', $key);
    }

    /**
     * Browse a single vertical (automotive / construction).
     */
    public function show(Request $request, string $vertical)
    {
        $config = config("marketplace.verticals.$vertical");

        // Unknown or the default ecommerce vertical → fall back to the normal home.
        if (! $config || ($config['type'] ?? 'ecommerce') === 'ecommerce') {
            $cookieKey = $config ? $vertical : config('marketplace.default');
            Cookie::queue('marketplace_vertical', $cookieKey, 525600);
            return redirect()->route('home');
        }

        // Keep the cookie in sync when a vertical page is opened directly.
        Cookie::queue('marketplace_vertical', $vertical, 525600);

        [$filters, $activeFilters] = $this->parseFilters($request, $config);

        $products = $this->productService->getVerticalListing($config['key'], $filters, 24)
            ->withQueryString();

        // Only derive dropdown values for attribute-backed selects (skip selects
        // that declare their own fixed options, e.g. availability).
        $selectKeys = collect($config['filters'])
            ->where('type', 'select')
            ->reject(fn ($f) => isset($f['options']) || in_array($f['key'], ['availability', 'stock_id'], true))
            ->pluck('key')
            ->all();

        $filterOptions = $this->productService->getVerticalFilterOptions($config['key'], $selectKeys);

        $component = $config['type'] === 'automotive'
            ? 'Frontend/Marketplace/Automotive'
            : 'Frontend/Marketplace/Construction';

        return Inertia::render($component, [
            'vertical'      => [
                'key'     => $config['key'],
                'label'   => $config['label'],
                'tagline' => $config['tagline'] ?? '',
                'type'    => $config['type'],
                'icon'    => $config['icon'] ?? 'ShoppingBag',
            ],
            'filterSchema'  => $config['filters'],
            'quickTags'     => $config['quick_tags'] ?? [],
            'sorts'         => $config['sorts'] ?? [],
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'products'      => $products,
            'title'         => $config['label'],
        ]);
    }

    /**
     * Submit a bulk-quote / inquiry (RFQ) for a listing. Open to guests.
     */
    public function quote(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'vertical'   => ['nullable', 'string', 'max:50'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
            'unit'       => ['nullable', 'string', 'max:50'],
            'message'    => ['nullable', 'string', 'max:2000'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return back(303)->withErrors(['email' => 'Provide an email or phone so we can reach you.']);
        }

        ProductQuoteRequest::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'status'  => 'new',
        ]);

        return back(303)->with('success', 'Your quote request has been sent. Our team will get back to you shortly.');
    }

    /**
     * Auto-detect license-plate regions in an uploaded car photo (ANPR model),
     * returned as fractional bounding boxes for the client-side blur editor.
     */
    public function detectPlates(Request $request, PlateDetectionService $detector)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:12288'], // 12 MB
        ]);

        if (! $detector->enabled()) {
            return response()->json(['enabled' => false, 'boxes' => []]);
        }

        return response()->json([
            'enabled' => true,
            'boxes'   => $detector->detect($request->file('image')),
        ]);
    }

    /**
     * Mark a car as reserved.
     */
    public function reserve(Request $request, Product $product)
    {
        $product->forceFill(['reserved_at' => now()])->saveQuietly();

        return back(303)->with('success', 'Listing reserved.');
    }

    /**
     * Remove a reservation (make the car available again).
     */
    public function unreserve(Request $request, Product $product)
    {
        $product->forceFill(['reserved_at' => null])->saveQuietly();

        return back(303)->with('success', 'Reservation removed.');
    }

    /**
     * Resolve a vertical's category slugs to the full set of category IDs
     * (including descendants), reusing Category::getAllCategoryIds().
     */
    protected function categoryIdsFor(array $config): array
    {
        return collect($config['category_slugs'] ?? [])
            ->flatMap(function ($slug) {
                $category = Category::where('slug', $slug)->first();
                return $category ? $category->getAllCategoryIds() : collect();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Translate request query params into the service filter structure, and a
     * flat `activeFilters` map echoed back to the frontend to seed the widgets.
     */
    protected function parseFilters(Request $request, array $config): array
    {
        $selects      = [];
        $ranges       = [];
        $active        = [];
        $availability = null;
        $stockId      = null;

        // Keys that filter the product row directly rather than the attributes bag.
        $reservedKeys = ['availability', 'stock_id'];

        foreach ($config['filters'] as $filter) {
            $key = $filter['key'];

            if ($filter['type'] === 'text') {
                $value = $request->query($key);
                if ($value !== null && $value !== '') {
                    if ($key === 'stock_id') {
                        $stockId = $value;
                    } else {
                        $selects[$key] = $value;
                    }
                    $active[$key] = $value;
                }
                continue;
            }

            if ($filter['type'] === 'select') {
                $value = $request->query($key);
                if ($value !== null && $value !== '') {
                    if ($key === 'availability') {
                        $availability = $value;
                    } elseif (! in_array($key, $reservedKeys, true)) {
                        $selects[$key] = $value;
                    }
                    $active[$key] = $value;
                }
                continue;
            }

            if ($filter['type'] === 'range') {
                $minParam = $filter['min'];
                $maxParam = $filter['max'];
                $min = $request->query($minParam);
                $max = $request->query($maxParam);

                if ($key === 'price') {
                    // Price ranges filter the variant selling_price, not attributes.
                    continue;
                }

                if (($min !== null && $min !== '') || ($max !== null && $max !== '')) {
                    $ranges[$key] = [
                        'min' => is_numeric($min) ? (float) $min : null,
                        'max' => is_numeric($max) ? (float) $max : null,
                    ];
                }
                if ($min !== null && $min !== '') {
                    $active[$minParam] = $min;
                }
                if ($max !== null && $max !== '') {
                    $active[$maxParam] = $max;
                }
            }
        }

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        if ($minPrice !== null && $minPrice !== '') {
            $active['min_price'] = $minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $active['max_price'] = $maxPrice;
        }

        $sort = $request->query('sort', 'newest');
        $active['sort'] = $sort;

        $filters = [
            'selects'      => $selects,
            'ranges'       => $ranges,
            'min_price'    => is_numeric($minPrice) ? (float) $minPrice : null,
            'max_price'    => is_numeric($maxPrice) ? (float) $maxPrice : null,
            'availability' => $availability,
            'stock_id'     => $stockId,
            'sort'         => $sort,
        ];

        return [$filters, $active];
    }

    protected function publicVerticals(): array
    {
        return collect(config('marketplace.verticals', []))
            ->map(fn ($v) => [
                'key'     => $v['key'],
                'label'   => $v['label'],
                'tagline' => $v['tagline'] ?? '',
                'type'    => $v['type'],
                'icon'    => $v['icon'] ?? 'ShoppingBag',
            ])
            ->values()
            ->all();
    }
}
