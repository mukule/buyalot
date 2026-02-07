<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Products\Product;
use App\Models\Region;
use App\Models\Seller\Seller;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Services\FrontendProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Policy\Policy;



use Illuminate\Support\Facades\Cache;


class HomeController extends Controller
{
    protected FrontendProductService $productService;

    public function __construct(FrontendProductService $productService)
    {
        $this->productService = $productService;
    }


    public function index()
{
    // 1. Light queries (Fast, no need to cache these specifically)
    $categories = \App\Models\Category::query()
        ->select('id', 'name', 'slug')
        ->with(['children:id,parent_id,name,slug'])
        ->whereNull('parent_id')
        ->orderBy('name')
        ->get();

    $brands = \App\Models\Brand::query()
        ->select('id', 'name', 'slug', 'logo_path')
        ->where('active', 1)
        ->orderBy('name')
        ->get();

    // 2. Heavy logic (The Service handles its own Redis caching internally)
    $productsByCategory = $this->productService->getProductsGroupedByCategory($categories);

    return Inertia::render('Frontend/Index', [
        'title' => 'Online Shopping Store',
        'categories' => $categories,
        'brands' => $brands->map(fn($brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo_url' => $brand->logo_url,
        ]),
        'productsByCategory' => $productsByCategory,
    ]);
}



public function productDetails(string $slug)
{
    $product = Product::with([
        'brand',
        'primaryImage',
        'images',
        'productVariants.values.variant',
        'category.parent',
        'warranties',
    ])->where('slug', $slug)->firstOrFail();

    $variants = $product->productVariants->map(function ($variant) {
        $discountPercent = 0;
        if ($variant->marked_price > 0 && $variant->discount > 0) {
            $discountPercent = round(($variant->discount / $variant->marked_price) * 100, 2);
        }

        return [
            'id'               => $variant->id,
            'marked_price'     => $variant->marked_price,
            'final_price'      => $variant->selling_price,
            'discount'         => $variant->discount,
            'discount_percent' => $discountPercent,
            'has_discount'     => $variant->discount > 0,
            'stock'            => $variant->stock,
            'sku'              => $variant->sku,
            'values'           => $variant->values->map(fn ($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value'               => $v->variant->value,
            ]),
        ];
    });

    // Determine selected variant
    $selectedVariant = $product->productVariants->first();
    if (request()->has('variant_id')) {
        $variantId = request('variant_id');
        $selectedVariant = $product->productVariants->firstWhere('id', $variantId) ?? $selectedVariant;
    }

    $relatedProducts = $selectedVariant
        ? $this->productService->getRelatedProducts($selectedVariant)
        : collect();

    $ownerInfo = $selectedVariant?->getOwnerInfo();
    $activeWarranty = $selectedVariant?->getActiveWarranty();

    $productData = [
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->name,
        'primary_image_url' => $product->primary_image_url, // accessor
        'stock' => $product->productVariants->sum('stock'),
        'category_hierarchy' => $product->category ? $product->category->getHierarchy() : [],
        'brand' => $product->brand ? [
            'id' => $product->brand->id,
            'name' => $product->brand->name,
        ] : null,
        'features' => $product->features,
        'description' => $product->description,
        'specifications' => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,
        'images' => $product->image_urls,
        'variants' => $variants,
        'owner' => $ownerInfo ? [
            'type' => $ownerInfo['type'],
            'name' => $ownerInfo['name'],
        ] : null,
        'warranty' => $activeWarranty ? [
            'id' => $activeWarranty->id,
            'duration' => $activeWarranty->duration,
            'description' => $activeWarranty->description,
        ] : null,
    ];

    if (!empty($product->video_url)) {
    $productData['video_url'] = $product->video_url;
}

    $cartVariantIds = [];
    $cart = app(\App\Services\CartService::class)->getCart(request());
    if ($cart) {
        $cartVariantIds = $cart->items()->pluck('product_variant_id')->toArray();
    }

    $shippingService = app(\App\Services\ShippingService::class);

    $regions = Region::with(['pickupPoints' => fn($q) => $q->active()])
        ->active()
        ->level('region')
        ->get()
        ->map(function ($region) use ($shippingService) {
            return [
                'id' => $region->id,
                'name' => $region->name,
                'pickup_points' => $region->pickupPoints->map(fn($pp) => [
                    'id' => $pp->id,
                    'name' => $pp->name,
                ])->values()->toArray(),
                'shipping_options' => $shippingService->getOptionsByRegion($region->id),
            ];
        });

    return Inertia::render('Frontend/ProductDetail', [
        'product' => $productData,
        'relatedProducts' => $relatedProducts,
        'cartVariantIds' => $cartVariantIds,
        'regions' => $regions,
        'title' => $product->name,
    ]);
}





public function category(string $slug)
{
    // Cache category + children for 30 minutes
    $category = Cache::remember("category_{$slug}", now()->addMinutes(30), function () use ($slug) {
        return Category::with(['children' => fn($q) => $q->active()->select('id', 'name', 'slug', 'parent_id')])
            ->select('id', 'name', 'slug')
            ->where('slug', $slug)
            ->firstOrFail();
    });

    // Get all category IDs (including subcategories)
    $categoryIds = $category->getAllCategoryIds()->toArray();

    // Selected filters
    $selectedSubcategory = request('subcategory') ? (int) request('subcategory') : null;
    $selectedBrands = collect(explode(',', request('brands', '')))
        ->filter()
        ->map(fn($id) => (int) $id)
        ->toArray();
    $minPrice = request('min_price') !== null ? (float) request('min_price') : null;
    $maxPrice = request('max_price') !== null ? (float) request('max_price') : null;

    // If a valid subcategory is selected, override category IDs
    if ($selectedSubcategory) {
        $subcategory = $category->children->firstWhere('id', $selectedSubcategory);
        if ($subcategory) {
            $categoryIds = $subcategory->getAllCategoryIds()->toArray();
        }
    }

    // Get paginated products (20 per page)
    $products = $this->productService->getPaginatedProductsByCategoryIds(
        $categoryIds,
        50,
        $minPrice,
        $maxPrice,
        $selectedBrands
    );

    // Cache brands separately for 1 hour
    $brands = Cache::remember('brands_list', now()->addHour(), function () {
        return \App\Models\Brand::select('id', 'name')->orderBy('name')->get();
    });

    return Inertia::render('Frontend/Category', [
        'category' => $category,
        'products' => $products,
        'subcategories' => $category->children,
        'selectedSubcategory' => $selectedSubcategory,
        'brands' => $brands,
        'selectedBrands' => $selectedBrands,
        'minPrice' => $minPrice,
        'maxPrice' => $maxPrice,
        'breadcrumbs' => $category->getHierarchy(),
        'title' => $category->name,
    ]);
}




    public function dashboard(Request $request)
    {
        $now = Carbon::now();

        // Determine user and base order scope
        $user = $request->user();
        $sellerIds = null;
        $orderBase = Order::query();
        if ($user && $user->hasRole(['seller','vendor'])) {
            $sellerTable = (new \App\Models\Seller\Seller())->getTable();
            $sellerIds = $user->sellers()->pluck($sellerTable . '.id');
            $orderBase->forSeller($sellerIds);
        }

        // Define week ranges
        $startOfThisWeek = $now->startOfWeek();
        $endOfThisWeek   = $now->copy()->endOfWeek();

        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek   = $now->copy()->subWeek()->endOfWeek();

        // Weekly orders (scoped if seller)
        $ordersThisWeek = (clone $orderBase)->whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $ordersLastWeek = (clone $orderBase)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

        $orderGrowth = $ordersLastWeek > 0
            ? round((($ordersThisWeek - $ordersLastWeek) / $ordersLastWeek) * 100, 2)
            : ($ordersThisWeek > 0 ? 100 : 0);

        // Build stats with role-aware visibility
        if ($user && $user->hasRole('seller')) {
            // For sellers: remove seller and customer totals, and scope orders and users to seller account(s)
            $stats = [
                'users'             => User::forSeller($sellerIds)->count(),
                'orders'            => (clone $orderBase)->count(),
                'warehouses'        => Warehouse::count(),
                'orders_this_week'  => $ordersThisWeek,
                'orders_last_week'  => $ordersLastWeek,
                'order_growth'      => $orderGrowth,
            ];
        } else {
            // Admins and other roles see global stats
            $stats = [
                'sellers'           => Seller::count(),
                'customers'         => Customer::count(),
                'users'             => User::where('user_type', '!=',['customer', 'seller','vendor'])->count(),
                'orders'            => Order::count(),
                'warehouses'        => Warehouse::count(),
                'orders_this_week'  => $ordersThisWeek,
                'orders_last_week'  => $ordersLastWeek,
                'order_growth'      => $orderGrowth,
            ];
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $ordersByStatus = (clone $orderBase)
            ->select(
                DB::raw('DATE(created_at) as date'),
                'status',
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('status');

        $productVariantPerformance = OrderItem::select(
            'product_variant_id',
            DB::raw('SUM(quantity) as total_sold')
        )
            ->groupBy('product_variant_id')
            ->with(['productVariant:id,product_id,sku', 'productVariant.product:id,name'])
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name'  => $item->productVariant?->display_name ?? 'Unknown Variant',
                    'total' => $item->total_sold,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ordersByStatus' => $ordersByStatus,
            'productVariantPerformance' => $productVariantPerformance,
        ]);
    }



    protected function logCategoryWithChildren(Category $category, int $level = 0): array
{
    $data = [
        'id' => $category->id,
        'name' => $category->name,
        'children' => [],
    ];

    foreach ($category->children as $child) {
        $data['children'][] = $this->logCategoryWithChildren($child, $level + 1);
    }

    return $data;
}



public function show($slug)
{
    $policy = Policy::where('slug', $slug)
        ->active()
        ->customer()
        ->with('latestActiveVersion')
        ->firstOrFail();

    $version = $policy->latestActiveVersion;

    if (!$version) {
    return Inertia::render('Frontend/Policy', [
        'policy' => [
            'title' => $policy->title,
            'content' => '<p>This policy is currently unavailable.</p>',
            'version' => null,
            'effective_from' => null,
        ]
    ]);
}


    return Inertia::render('Frontend/Policy', [
        'policy' => [
            'title' => $policy->title,
            'content' => $version->content,
            'version' => $version->version_number,
            'effective_from' => $version->effective_from_local,
        ]
    ]);
}




}


