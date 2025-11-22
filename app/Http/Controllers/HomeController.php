<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Product;
use App\Models\Seller\Seller;
use App\Models\User;
use App\Models\Region;
use App\Models\PickupPoint;
use App\Models\Warehouse\Warehouse;
use App\Services\FrontendProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class HomeController extends Controller
{
    protected FrontendProductService $productService;

    public function __construct(FrontendProductService $productService)
    {
        $this->productService = $productService;
    }


    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();
        $brands = Brand::all();

        $productsByCategory = $this->productService->getProductsGroupedByCategory($categories);
        // info($productsByCategory);

        return Inertia::render('Frontend/Index', [
            'title' => 'Online Shopping Store',
            'categories' => $categories,
            'brands' => $brands,
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

    $variantIds = $product->productVariants->pluck('id')->toArray();
    $discountResults = app(\App\Services\DiscountService::class)->calculateDiscounts($variantIds);
    $discountLookup = collect($discountResults)->keyBy('product_variant_id');

    $variants = $product->productVariants->map(function ($variant) use ($discountLookup) {
        $discountData = $discountLookup->get($variant->id);

        $markedPrice = (float) $variant->marked_price;
        $finalPrice = $discountData['final_price'] ?? $markedPrice;
        $totalDiscount = $discountData['total_discount'] ?? 0;

        $discountPercent = $markedPrice > 0
            ? round(($totalDiscount / $markedPrice) * 100, 2)
            : 0;

        return [
            'id' => $variant->id,
            'marked_price' => round($markedPrice, 2),
            'final_price' => round($finalPrice, 2),
            'discount_percent' => $discountPercent,
            'has_discount' => $totalDiscount > 0,
            'stock' => $variant->stock,
            'sku' => $variant->sku,
            'values' => $variant->values->map(fn($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value' => $v->variant->value,
            ]),
        ];
    });

    // Determine selected variant
    $selectedVariant = $product->productVariants->first();
    if (request()->has('variant_id')) {
        $variantId = request('variant_id');
        $selectedVariant = $product->productVariants->firstWhere('id', $variantId) ?? $selectedVariant;
    }

    $relatedProducts = $this->productService->getRelatedProducts($selectedVariant);

    // Owner info
    $ownerInfo = $selectedVariant->getOwnerInfo();

    // Active warranty for the selected variant
    $activeWarranty = $selectedVariant->getActiveWarranty();

    $productData = [
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->name,
        'primary_image_url' => $product->primary_image_url,
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
        'images' => $product->images
            ->map(fn($img) => Storage::disk('s3')->url($img->image_path))
            ->toArray(),
        'variants' => $variants,
        'owner' => [
            'type' => $ownerInfo['type'],
            'name' => $ownerInfo['name'],
        ],
        'warranty' => $activeWarranty ? [
            'id' => $activeWarranty->id,
            'duration' => $activeWarranty->duration,
            'description' => $activeWarranty->description,
        ] : null,
    ];

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
    $category = Category::with('children')->where('slug', $slug)->firstOrFail();

    $categoryIds = $category->getAllCategoryIds()->toArray();


    $selectedSubcategory = request('subcategory');
    $selectedSubcategory = $selectedSubcategory ? (int) $selectedSubcategory : null;

    $selectedBrands = collect(explode(',', request('brands', '')))
        ->filter()
        ->map(fn($id) => (int) $id)
        ->toArray();

    $minPrice = request('min_price') !== null ? (float) request('min_price') : null;
    $maxPrice = request('max_price') !== null ? (float) request('max_price') : null;


    if ($selectedSubcategory) {
        $subcategory = Category::find($selectedSubcategory);
        if ($subcategory && $subcategory->parent_id === $category->id) {
            $categoryIds = $subcategory->getAllCategoryIds()->toArray();
        }
    }


    $products = $this->productService->getPaginatedProductsByCategoryIds(
        $categoryIds,
        20,
        $minPrice,
        $maxPrice,
        $selectedBrands
    );


    return Inertia::render('Frontend/Category', [
        'category' => $category,
        'products' => $products,
        'subcategories' => $category->children()->active()->get(['id', 'name', 'slug']),
        'selectedSubcategory' => $selectedSubcategory,
        'brands' => \App\Models\Brand::select('id', 'name')->get(),
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
        if ($user && $user->hasRole('seller')) {
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
                'users'             => User::count(),
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
}


