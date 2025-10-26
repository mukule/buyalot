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
use App\Models\Warehouse\Warehouse;
use App\Services\FrontendProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;


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
//        info("product details "+$slug);
        $product = Product::with([
            'brand',
            'primaryImage',
            'images',
            'productVariants.values.variant',
            'category.parent',
        ])->where('slug', $slug)->firstOrFail();

        $variants = $product->productVariants->map(fn($variant) => [
            'id' => $variant->id,
            'regular_price' => $variant->regular_price,
            'selling_price' => $variant->selling_price,
//            'discount'       => ($variant->regular_price > 0 && $variant->regular_price > $variant->selling_price)
//                ? round((($variant->regular_price - $variant->selling_price) / $variant->regular_price) * 100)
//                : null,
            'discount' => ($variant->regular_price > 0 && $variant->regular_price > $variant->selling_price)
                ? (int)round((($variant->regular_price - $variant->selling_price) / $variant->regular_price) * 100)
                : 0,
            'stock' => $variant->stock,
            'sku' => $variant->sku,
            'values' => $variant->values->map(fn($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value' => $v->variant->value,
            ]),
        ]);

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
        ];

        $relatedProducts = $this->productService->getRelatedProducts($product);

        $cartVariantIds = [];
        $cart = app(\App\Services\CartService::class)->getCart(request());
        if ($cart) {
            $cartVariantIds = $cart->items()->pluck('product_variant_id')->toArray();
        }

        return Inertia::render('Frontend/ProductDetail', [
            'product' => $productData,
            'relatedProducts' => $relatedProducts,
            'cartVariantIds' => $cartVariantIds,
            'title' => $product->name,
        ]);
    }

   
    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        
        $products = $this->productService->getPaginatedProductsByCategory($category, 20);

        return Inertia::render('Frontend/Category', [
            'category' => $category,
            'products' => $products,
            'breadcrumbs' => $category->getHierarchy(),
            'title' => $category->name,
        ]);
    }

    public function dashboard(Request $request)
    {
        $now = Carbon::now();

        // Define week ranges
        $startOfThisWeek = $now->startOfWeek();
        $endOfThisWeek   = $now->copy()->endOfWeek();

        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek   = $now->copy()->subWeek()->endOfWeek();

        // Weekly orders
        $ordersThisWeek = Order::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
        $ordersLastWeek = Order::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

        $orderGrowth = $ordersLastWeek > 0
            ? round((($ordersThisWeek - $ordersLastWeek) / $ordersLastWeek) * 100, 2)
            : ($ordersThisWeek > 0 ? 100 : 0);

        $stats = [
            'sellers'         => Seller::count(),
            'customers'       => Customer::count(),
            'users'           => User::count(),
            'orders'          => Order::count(),
            'warehouses'      => Warehouse::count(),
            'orders_this_week' => $ordersThisWeek,
            'orders_last_week' => $ordersLastWeek,
            'order_growth'     => $orderGrowth,
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $ordersByStatus = Order::select(
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


