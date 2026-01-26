<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshProductCache;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\VariantCategory;
use App\Models\Category;
use App\Services\SearchCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductStatus;
use App\Models\Scopes\SellerProductScope;


class ProductController extends Controller
{


public function index()
{
    $user = auth()->user();

    $query = Product::with(['primaryImage', 'productVariants', 'category', 'owner.roles', 'warranties'])
        ->orderBy('created_at', 'desc');

    if ($user->hasRole('seller')) {
        $query->where('owner_type', 'seller')
              ->where('owner_id', $user->id);
    }

    $products = $query->paginate(15)
        ->through(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'product_code' => $product->product_code,
                'primary_image_url' => $product->primary_image_url,
                'stock' => $product->productVariants->sum('stock'),
                'hashid' => $product->hashid,
                'status_id' => $product->status_id ?? null,
                'status_label' => $product->status_label ?? 'Draft',
                'category' => $product->category
                    ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ]
                    : null,
                'owner' => [
                    'id' => $product->owner?->id,
                    'name' => $product->company_legal_name,
                ],
                'warranties' => $product->warranties->map(fn($warranty) => [
                    'id' => $warranty->id,
                    'hashid' => $warranty->hashid,
                    'duration' => $warranty->duration,
                    'description' => $warranty->description,
                    'active' => $warranty->active,
                ]),
                'active_warranty' => $product->activeWarranty()?->only(['id','duration','description','active']) ?? null,
            ];
        });

    // Load statuses with seller restrictions
    $statusesQuery = ProductStatus::orderBy('name');
    if ($user->hasRole('seller')) {
        $statusesQuery->whereIn('name', ['draft', 'submit', 'pause']);
    }

    $statuses = $statusesQuery->get(['id', 'name', 'label', 'color_class'])
        ->map(fn($status) => [
            'id' => $status->id,
            'name' => $status->name,
            'label' => $status->label,
            'color_class' => $status->color_class,
        ])->keyBy('id')->toArray();

    // Include current product statuses if missing
    foreach ($products as $product) {
        if ($product['status_id'] && !isset($statuses[$product['status_id']])) {
            $currentStatus = ProductStatus::find($product['status_id']);
            if ($currentStatus) {
                $statuses[$currentStatus->id] = [
                    'id' => $currentStatus->id,
                    'name' => $currentStatus->name,
                    'label' => $currentStatus->label,
                    'color_class' => $currentStatus->color_class,
                ];
            }
        }
    }

    // Re-index numerically for Inertia
    $statuses = array_values($statuses);

    return Inertia::render('Admin/Products/Index', [
        'products' => $products,
        'productStatuses' => $statuses,
    ]);
}


public function create()
{
    $brands = Brand::where('active', true)->get();
    $units = Unit::where('active', true)->get();

    $categories = Category::active()
        ->whereNull('parent_id')
        ->with('children')
        ->get();

    // Get the latest draft product if exists
    $draftProduct = auth()->user()->products()->latestDraft()->first();

    $selectedCategoryId = $draftProduct?->category_id ?? null;

    // Fetch variant categories based on selected category
    $variantCategories = VariantCategory::when($selectedCategoryId, function ($query, $categoryId) {
        $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
    })
    ->with(['variants' => fn($q) => $q->where('is_active', true)])
    ->get();

    // Fallback to default if none found
    if ($variantCategories->isEmpty()) {
        $variantCategories = VariantCategory::where('default', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->get();
    }

    // Map for frontend
    $variantCategories = $variantCategories->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'options' => $category->variants->map(fn($v) => [
                'id' => $v->id,
                'value' => $v->value,
            ])->toArray(),
        ];
    });

    // Prepare variant rows if draft exists
    $variantRows = [];
    if ($draftProduct) {
        $variantRows = $draftProduct->variants()
            ->with('values.variant')
            ->get()
            ->map(function ($variant) {
                $row = [
                    'id' => $variant->id,
                    'marked_price' => $variant->marked_price,
                    'buying_price' => $variant->selling_price,
                    'stock' => $variant->stock,
                    'sku' => $variant->sku,
                    'values' => [],
                ];

                foreach ($variant->values as $pvValue) {
                    $row['values'][$pvValue->variant->variant_category_id] = $pvValue->variant->value;
                }

                return $row;
            })
            ->toArray();
    }

    // Prepare draft product data
    $productData = $draftProduct ? [
        'product_id' => $draftProduct->id,
        'id' => $draftProduct->id,
        'current_step' => $draftProduct->current_step,
        'max_step_completed' => $draftProduct->max_step_completed,
        'product_code' => $draftProduct->product_code,
        'name' => $draftProduct->name,
        'category_id' => $draftProduct->category_id,
        'brand_id' => $draftProduct->brand_id,
        'unit_id' => $draftProduct->unit_id,
        'description' => $draftProduct->description,
        'features' => $draftProduct->features,
        'specifications' => $draftProduct->specifications,
        'whats_in_the_box' => $draftProduct->whats_in_the_box,
        'variant_rows' => $variantRows,
        'images' => $draftProduct->images ?? [],
    ] : null;

    return Inertia::render('Admin/Products/Create', [
        'brands' => $brands,
        'categories' => $categories,
        'units' => $units,
        'variantCategories' => $variantCategories,
        'product' => $productData,
    ]);
}




public function edit(Product $product)
{
    $brands = Brand::where('active', true)->get();
    $units = Unit::where('active', true)->get();

    $categories = Category::active()
        ->whereNull('parent_id')
        ->with('children')
        ->get();

    $variantCategories = VariantCategory::whereHas('categories', function ($query) use ($product) {
            $query->where('categories.id', $product->category_id);
        })
        ->with(['variants' => fn ($query) => $query->where('is_active', true)])
        ->get();

    if ($variantCategories->isEmpty()) {
        $variantCategories = VariantCategory::where('default', true)
            ->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->get();
    }

    $variantCategories = $variantCategories->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'options' => $category->variants->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
            ])->toArray(),
        ];
    });

    $variantRows = $product->variants()
        ->with('values.variant')
        ->get()
        ->map(function ($variant) {
            $row = [
                'id' => $variant->id,
                'marked_price' => $variant->marked_price,
                'buying_price' => $variant->selling_price,
                'stock' => $variant->stock,
                'sku' => $variant->sku,
                'values' => [],
            ];

            foreach ($variant->values as $pvValue) {
                $row['values'][$pvValue->variant->variant_category_id] =
                    $pvValue->variant->value;
            }

            return $row;
        })
        ->toArray();

    $images = collect($product->images ?? [])->map(function ($img) {
        $path = $img->url ?? $img->image_path ?? '';
        return [
            'id' => $img->id,
            'file' => null,
            'preview' => $path ? Storage::url($path) : '',
            'url' => $path ? Storage::url($path) : '',
            'is_primary' => $img->is_primary ?? false,
        ];
    })->toArray();

    $productData = [
        'product_id' => $product->id,
        'id' => $product->id,
        'current_step' => $product->current_step,
        'max_step_completed' => $product->max_step_completed,
        'product_code' => $product->product_code,
        'name' => $product->name,
        'category_id' => $product->category_id,
        'brand_id' => $product->brand_id,
        'unit_id' => $product->unit_id,
        'description' => $product->description,
        'features' => $product->features,
        'specifications' => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,
        'video_url' => $product->video_url,
        'variant_rows' => $variantRows,
        'images' => $images,
    ];

    return Inertia::render('Admin/Products/Create', [
        'brands' => $brands,
        'categories' => $categories,
        'units' => $units,
        'variantCategories' => $variantCategories,
        'product' => $productData,
    ]);
}


public function store(Request $request, ProductService $productService)
{
    $step = (int) $request->input('step');
    $data = $request->all();
    $images = $request->hasFile('images') ? $request->file('images') : [];

    // 1. Gather ID candidates from all possible sources
    $productIdFromRequest = $request->input('product_id');
    $productIdFromSession = session('product_id');
    $productIdFromInput = $request->old('product_id');
   

    $product = null;

    // 2. Attempt to resolve product for Steps 2, 3, and 4
    if ($step > 1) {
        // We prioritize Request, then Session, then Old Input
        $idToFind = $productIdFromRequest ?? $productIdFromSession ?? $productIdFromInput;

        if ($idToFind) {
            // We use withoutGlobalScopes() to bypass the SellerProductScope filter
            $product = \App\Models\Product::withoutGlobalScopes()->find($idToFind);
            
          
        } else {
            \Log::warning("CRITICAL: Step {$step} initiated but NO Product ID found in Request or Session.");
        }

        // 3. Manual Security Guard (Since we bypassed Global Scopes)
        if ($product && $request->user()->user_type === 'seller') {
            if ((int) $product->owner_id !== (int) $request->user()->id) {
                \Log::error("SECURITY ALERT: Seller " . auth()->id() . " tried to access Product " . $product->id);
                abort(403, 'Unauthorized access to this product.');
            }
        }
    }

    try {
        // 4. Pass the resolved product (or null for Step 1) to the Service
        $product = $productService->createOrUpdateProductStep(
            $step,
            $data,
            $request->user(),
            $images,
            $product
        );

        if ($step === 4) {
            return redirect()->route('admin.products.index')
                ->with('success', "Product '{$product->name}' created successfully.");
        }

        // 5. Success Redirection: We flash the ID to the session explicitly
        return back()
            ->with('success', "Step {$step} completed successfully.")
            ->with('step', $product->current_step)
            ->with('product_id', $product->id);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error("Step {$step} Validation Failed", ['errors' => $e->errors()]);
        return back()
            ->withErrors($e->errors())
            ->withInput() // This keeps product_id in old() input
            ->with([
                'step' => $step,
                'product_id' => $product?->id,
            ]);

    } catch (\Throwable $e) {
       

        return back()
            ->with('error', "System Error: " . $e->getMessage())
            ->with('step', $step)
            ->with('product_id', $product?->id);
    }
}

    public function destroy(Product $product)
{
    // Delete all product images from storage
    foreach ($product->images ?? [] as $image) {
        if (!empty($image['file_path']) && Storage::exists($image['file_path'])) {
            Storage::delete($image['file_path']);
        }
    }

    // Delete related variants
    if (method_exists($product, 'variants')) {
        $product->variants()->delete();
    }

    if (method_exists($product, 'variant_rows')) {
        $product->variant_rows()->delete();
    }

    // Delete the product itself
    $product->delete();

// Remove product and its variants from cache
        $cache = SearchCacheService::get();
        $productId = $product->id;
        $cache['products'] = array_filter($cache['products'] ?? [], fn($p) => $p['id'] !== $productId);
        $cache['variants'] = array_filter($cache['variants'] ?? [], fn($v) => $v['product_id'] !== $productId);
        Cache::put(SearchCacheService::CACHE_KEY, $cache, now()->addMonths(6));
    return redirect()
        ->route('admin.products.index')
        ->with('success', 'Product and all related data deleted successfully.');
}



public function destroyAll()
{
    DB::beginTransaction(); // Start a transaction

    try {
        $products = Product::all();

        foreach ($products as $product) {
            // Delete images
            foreach ($product->images ?? [] as $image) {
                if (!empty($image['file_path']) && Storage::exists($image['file_path'])) {
                    Storage::delete($image['file_path']);
                }
            }

            // Delete related variants safely
            if (method_exists($product, 'variants')) {
                $product->variants()->delete(); // Use delete() for HasMany
            }

            if (method_exists($product, 'variant_rows')) {
                $product->variant_rows()->delete();
            }

            // Delete the product itself
            $product->delete();
        }

        DB::commit(); // Commit the transaction

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'All products and related data deleted successfully.');

    } catch (\Exception $e) {
        DB::rollBack(); // Rollback if any error occurs

        \Log::error('Error deleting all products: ' . $e->getMessage());

        return redirect()
            ->route('admin.products.index')
            ->with('error', 'An error occurred while deleting the products.');
    }
}

public function show(Product $product)
{
    $product->load([
        'primaryImage',
        'images',
        'productVariants.values.variant',
        'category.parent',
        'owner.roles',
        'brand',
        'unit',
    ]);

    $productData = [
        'id' => $product->id,
        'hashid' => $product->hashid,
        'name' => $product->name,
        'product_code' => $product->product_code,
        'primary_image_url' => $product->primary_image_url, // use accessor
        'stock' => $product->productVariants->sum('stock'),
        'category_hierarchy' => $product->category ? $product->category->getHierarchy() : [],

        'owner' => $product->owner ? [
            'id' => $product->owner->id,
            'name' => $product->owner->name,
            'roles' => $product->owner->getRoleNames()->toArray(),
        ] : null,

        'brand' => $product->brand ? [
            'id' => $product->brand->id,
            'name' => $product->brand->name,
        ] : null,

        'unit' => $product->unit ? [
            'id' => $product->unit->id,
            'name' => $product->unit->name,
        ] : null,

        'features' => $product->features,
        'description' => $product->description,
        'specifications' => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,

        // ✅ Use accessor for all image URLs
        'images' => $product->image_urls,
        'image_urls' => $product->image_urls,

        'variants' => $product->productVariants->map(fn($variant) => [
            'id' => $variant->id,
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->selling_price,
            'stock' => $variant->stock,
            'sku' => $variant->sku,
            'values' => $variant->values->map(fn($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value' => $v->variant->value,
            ]),
        ]),
    ];

    return Inertia::render('Admin/Products/Show', [
        'product' => $productData,
    ]);
}


public function destroyImage(Product $product, int $imageId)
{
    \Log::info('destroyImage called', [
        'product_id' => $product->id,
        'image_id' => $imageId,
        'user_id' => auth()->id(),
    ]);

    // Find the image for this product
    $image = $product->images()->where('id', $imageId)->first();

    if (!$image) {
        \Log::warning('destroyImage: Image not found', [
            'product_id' => $product->id,
            'image_id' => $imageId,
        ]);
        return redirect()->back()->with('error', 'Image not found for this product.');
    }

    // Delete the file from storage
    if (!empty($image->image_path) && Storage::exists($image->image_path)) {
        Storage::delete($image->image_path);
        \Log::info('destroyImage: Image file deleted from storage', [
            'image_path' => $image->image_path,
        ]);
    }

    // Delete the database record
    $image->delete();
    \Log::info('destroyImage: Image record deleted from DB', [
        'image_id' => $imageId,
    ]);

    return redirect()->back()->with('success', 'Image deleted successfully.');
}


public function updateStatus(Request $request, Product $product)
{
    $request->validate([
        'status_id' => ['required', 'exists:product_statuses,id'],
    ]);

    // This 'update' call triggers the 'saved' event, 
    // which the ProductObserver handles automatically!
    $product->update([
        'status_id' => $request->input('status_id'),
    ]);

    return redirect()
        ->back()
        ->with('success', 'Product status updated successfully.');
}


}
