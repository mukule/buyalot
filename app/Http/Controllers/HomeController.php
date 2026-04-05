<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderReturn;
use App\Models\Products\Product;
use App\Models\Region;
use App\Models\Seller\Seller;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseProductInventory;
use App\Models\Warehouse\WarehouseReceivable;
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
    // 1. Light queries
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

    // 2. Promotions (cached)
    $promotions = Cache::remember('promotions:homepage:category', now()->addMinutes(30), function () {
        return \App\Models\Promotion::active()
            ->where('link_type', 'category')
            ->with(['category:id,slug'])
            ->orderBy('priority')
            ->get()
            ->map(fn($promotion) => [
                'image_url'     => $promotion->image_url,
                'category_slug' => $promotion->category?->slug,
            ]);
    });

    // 3. Heavy logic
    $productsByCategory = $this->productService->getProductsGroupedByCategory($categories);

    return Inertia::render('Frontend/Index', [
        'title'              => 'Online Shopping Store',
        'categories'         => $categories,
        'brands'             => $brands->map(fn($brand) => [
            'id'       => $brand->id,
            'name'     => $brand->name,
            'slug'     => $brand->slug,
            'logo_url' => $brand->logo_url,
        ]),
        'promotions'         => $promotions,
        'productsByCategory' => $productsByCategory,
    ]);
}

public function productDetails(string $slug)
{
    $product = Product::with([
        'brand',
        'primaryImage',
        'images',
        'productVariants.values.variant.category', // added .category
        'productVariants.images',
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
            'images' => $variant->images->sortBy('sort_order')->map(fn ($img) => [
                'id'         => $img->id,
                'url'        => $img->url,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
            ])->values(),
        ];
    });

    // Variant attributes for selector UI
    $variantAttributes = $product->productVariants
        ->flatMap(fn ($variant) => $variant->values)
        ->groupBy(fn ($v) => $v->variant->category->name)
        ->map(fn ($group, $categoryName) => [
            'name'    => $categoryName,
            'options' => $group->map(fn ($v) => $v->variant->value)->unique()->values(),
        ])
        ->values();

    // Variant map keyed by "Red|M|Cotton" for instant frontend lookup
    $variantMap = $product->productVariants
        ->keyBy(fn ($variant) => $variant->values
            ->sortBy(fn ($v) => $v->variant->variant_category_id)
            ->map(fn ($v) => $v->variant->value)
            ->join('|')
        )
        ->map(fn ($variant) => [
            'id'          => $variant->id,
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->selling_price,
            'stock'       => $variant->stock,
            'sku'         => $variant->sku,
            'has_discount'     => $variant->discount > 0,
            'discount'         => $variant->discount,
            'discount_percent' => $variant->marked_price > 0 && $variant->discount > 0
                ? round(($variant->discount / $variant->marked_price) * 100, 2)
                : 0,
            'images' => $variant->images->sortBy('sort_order')->map(fn ($img) => [
                'id'         => $img->id,
                'url'        => $img->url,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
            ])->values(),
        ]);

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

    $selectedVariantPrimaryImage = $selectedVariant?->images->firstWhere('is_primary', true);
    $primaryImageUrl = $selectedVariantPrimaryImage?->url ?? $product->primary_image_url;

    $productData = [
        'id'                 => $product->id,
        'slug'               => $product->slug,
        'name'               => $product->name,
        'primary_image_url'  => $primaryImageUrl,
        'stock'              => $product->productVariants->sum('stock'),
        'category_hierarchy' => $product->category ? $product->category->getHierarchy() : [],
        'brand'              => $product->brand ? [
            'id'   => $product->brand->id,
            'name' => $product->brand->name,
        ] : null,
        'features'           => $product->features,
        'description'        => $product->description,
        'specifications'     => $product->specifications,
        'whats_in_the_box'   => $product->whats_in_the_box,
        'images'             => $product->image_urls,
        'variants'           => $variants,
        'variant_attributes' => $variantAttributes,
        'variant_map'        => $variantMap,
        'owner'              => $ownerInfo ? [
            'type' => $ownerInfo['type'],
            'name' => $ownerInfo['name'],
        ] : null,
        'warranty'           => $activeWarranty ? [
            'id'          => $activeWarranty->id,
            'duration'    => $activeWarranty->duration,
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

    $regions = Region::active()
        ->level('region')
        ->get()
        ->map(function ($region) use ($shippingService) {
            $warehouses = Warehouse::withoutGlobalScopes()
                ->where(function ($q) use ($region) {
                    $q->where('region_id', $region->id)
                        ->orWhereHas('regions', fn ($r) => $r->where('regions.id', $region->id));
                })
                ->whereIn('type', ['pickup_point', 'dispatch_center', 'general'])
                ->where('active', true)
                ->get(['id', 'name', 'address', 'location', 'latitude', 'longitude']);

            return [
                'id'               => $region->id,
                'name'             => $region->name,
                'pickup_points'    => $warehouses->map(fn ($w) => [
                    'id'        => $w->id,
                    'name'      => $w->name,
                    'address'   => $w->address,
                    'location'  => $w->location,
                    'latitude'  => $w->latitude ? (float) $w->latitude : null,
                    'longitude' => $w->longitude ? (float) $w->longitude : null,
                ])->values()->toArray(),
                'shipping_options' => $shippingService->getOptionsByRegion($region->id),
            ];
        });

    $googleMapsApiKey = config('services.google.maps_api_key', '');

    return Inertia::render('Frontend/ProductDetail', [
        'product'          => $productData,
        'relatedProducts'  => $relatedProducts,
        'cartVariantIds'   => $cartVariantIds,
        'regions'          => $regions,
        'googleMapsApiKey' => $googleMapsApiKey,
        'title'            => $product->name,
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

        // Recent returns (scoped to seller orders when seller/vendor)
        $returnsQuery = OrderReturn::with('order:id,order_code,ulid,status')
            ->latest()
            ->limit(15);
        if ($user && $user->hasRole(['seller', 'vendor']) && $sellerIds !== null) {
            $returnsQuery->whereHas('order', fn ($q) => $q->forSeller($sellerIds));
        }
        $returns = $returnsQuery->get()->map(function ($r) {
            $reasonLabel = OrderReturn::reasonOptions()[$r->reason] ?? $r->reason;
            return [
                'id' => $r->id,
                'order_id' => $r->order_id,
                'order_code' => $r->order?->order_code,
                'order_ulid' => $r->order?->ulid,
                'order_status' => $r->order?->status,
                'status' => $r->status,
                'reason' => $reasonLabel,
                'reason_notes' => $r->reason_notes,
                'is_full_return' => $r->is_full_return,
                'created_at' => $r->created_at?->toDateTimeString(),
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ordersByStatus' => $ordersByStatus,
            'productVariantPerformance' => $productVariantPerformance,
            'returns' => $returns,
        ]);
    }

    /**
     * Admin returns list (full page).
     */
    public function returnsIndex(Request $request)
    {
        $user = $request->user();
        $sellerIds = null;
        if ($user && $user->hasRole(['seller', 'vendor'])) {
            $sellerIds = $user->sellers()->pluck((new \App\Models\Seller\Seller())->getTable() . '.id');
        }

        $query = OrderReturn::with('order:id,order_code,ulid,status')
            ->latest();
        if ($user && $user->hasRole(['seller', 'vendor']) && $sellerIds !== null) {
            $query->whereHas('order', fn ($q) => $q->forSeller($sellerIds));
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->paginate($perPage)->withQueryString();

        $returns = $paginated->getCollection()->map(function ($r) {
            $reasonLabel = OrderReturn::reasonOptions()[$r->reason] ?? $r->reason;
            return [
                'id' => $r->id,
                'order_id' => $r->order_id,
                'order_code' => $r->order?->order_code,
                'order_ulid' => $r->order?->ulid,
                'order_status' => $r->order?->status,
                'status' => $r->status,
                'reason' => $reasonLabel,
                'reason_notes' => $r->reason_notes,
                'is_full_return' => $r->is_full_return,
                'created_at' => $r->created_at?->toDateTimeString(),
            ];
        });

        return Inertia::render('Admin/Returns/Index', [
            'returns' => $returns,
            'pagination' => [
                'links' => $paginated->toArray()['links'] ?? [],
                'meta' => $paginated->toArray(),
            ],
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Admin return detail view.
     */
    public function returnsShow(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user();
        $sellerIds = null;
        if ($user && $user->hasRole(['seller', 'vendor'])) {
            $sellerIds = $user->sellers()->pluck((new Seller())->getTable() . '.id');
        }

        $orderReturn->load([
            'order:id,order_code,ulid,status',
            'delivery.dispatchingWarehouse',
            'items.orderItem.productVariant.product',
        ]);

        if ($user && $user->hasRole(['seller', 'vendor']) && $sellerIds !== null) {
            if (! $orderReturn->order || ! Order::where('id', $orderReturn->order_id)->forSeller($sellerIds)->exists()) {
                abort(403, 'You do not have access to this return.');
            }
        }

        $dispatchingWarehouse = $orderReturn->delivery?->dispatchingWarehouse;
        $pendingReceivablesCount = 0;
        if ($dispatchingWarehouse && $orderReturn->status === OrderReturn::STATUS_PENDING_RECEIVE) {
            $pendingReceivablesCount = WarehouseReceivable::where('order_return_id', $orderReturn->id)
                ->where('warehouse_id', $dispatchingWarehouse->id)
                ->where('status', 'pending')
                ->count();
        }

        $reasonLabel = OrderReturn::reasonOptions()[$orderReturn->reason] ?? $orderReturn->reason;
        $items = $orderReturn->items->map(function ($ri) {
            $pv = $ri->orderItem?->productVariant;
            $product = $pv?->product;
            return [
                'id' => $ri->id,
                'product_name' => $product?->name ?? '—',
                'variant_display' => $pv ? ($pv->display_name !== 'Unnamed Variant' ? $pv->display_name : ($pv->sku ?: '—')) : '—',
                'quantity_returned' => $ri->quantity_returned,
            ];
        });

        return Inertia::render('Admin/Returns/Show', [
            'return' => [
                'id' => $orderReturn->id,
                'order_id' => $orderReturn->order_id,
                'order_code' => $orderReturn->order?->order_code,
                'order_ulid' => $orderReturn->order?->ulid,
                'order_status' => $orderReturn->order?->status,
                'status' => $orderReturn->status,
                'reason' => $reasonLabel,
                'reason_notes' => $orderReturn->reason_notes,
                'is_full_return' => $orderReturn->is_full_return,
                'raised_by_type' => $orderReturn->raised_by_type,
                'created_at' => $orderReturn->created_at?->toDateTimeString(),
                'received_at_dispatch_at' => $orderReturn->received_at_dispatch_at?->toDateTimeString(),
                'items' => $items,
                'dispatching_warehouse' => $dispatchingWarehouse ? [
                    'id' => $dispatchingWarehouse->id,
                    'hashid' => $dispatchingWarehouse->hashid,
                    'name' => $dispatchingWarehouse->name,
                ] : null,
                'can_receive' => $orderReturn->status === OrderReturn::STATUS_PENDING_RECEIVE && $pendingReceivablesCount > 0,
                'pending_receivables_count' => $pendingReceivablesCount,
            ],
        ]);
    }

    /**
     * Receive a return at the dispatching warehouse (accept all pending receivables).
     */
    public function receiveReturn(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user();
        $sellerIds = null;
        if ($user && $user->hasRole(['seller', 'vendor'])) {
            $sellerIds = $user->sellers()->pluck((new Seller())->getTable() . '.id');
        }

        $orderReturn->load('order', 'delivery.dispatchingWarehouse');
        if (! $orderReturn->order || ! $orderReturn->delivery) {
            return back()->with('error', 'Return has no linked order or delivery.');
        }
        if ($user && $user->hasRole(['seller', 'vendor']) && $sellerIds !== null) {
            if (! Order::where('id', $orderReturn->order_id)->forSeller($sellerIds)->exists()) {
                abort(403, 'You do not have access to this return.');
            }
        }

        if ($orderReturn->status !== OrderReturn::STATUS_PENDING_RECEIVE) {
            return back()->with('error', 'This return has already been received.');
        }

        $warehouse = $orderReturn->delivery->dispatchingWarehouse;
        if (! $warehouse) {
            return back()->with('error', 'No dispatching warehouse linked to this return.');
        }

        $receivables = WarehouseReceivable::where('order_return_id', $orderReturn->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->get();

        if ($receivables->isEmpty()) {
            return back()->with('error', 'No pending items to receive for this return.');
        }

        DB::transaction(function () use ($receivables, $warehouse, $orderReturn) {
            foreach ($receivables as $receivable) {
                $inv = WarehouseProductInventory::lockForUpdate()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $receivable->product_variant_id,
                ], [
                    'stock' => 0,
                    'reserved_stock' => 0,
                    'damaged_stock' => 0,
                    'cost_price' => 0,
                ]);
                $before = $inv->stock;
                $inv->stock += (int) $receivable->quantity;
                $inv->save();

                WarehouseInventoryMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $receivable->product_variant_id,
                    'type' => 'receive',
                    'quantity' => (int) $receivable->quantity,
                    'user_id' => auth()->id(),
                    'before_stock' => $before,
                    'after_stock' => $inv->stock,
                    'note' => 'Return received: ' . ($receivable->note ?? ''),
                ]);

                $receivable->status = 'received';
                $receivable->received_by = auth()->id();
                $receivable->received_at = now();
                $receivable->save();
            }

            $orderReturn->update([
                'status' => OrderReturn::STATUS_RECEIVED_AT_DISPATCH,
                'received_at_dispatch_at' => now(),
                'received_at_dispatch_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.returns.show', $orderReturn->id)
            ->with('success', 'Return received successfully. Items have been added to warehouse inventory.');
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


