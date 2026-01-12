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

    $variantCategories = VariantCategory::where('default', true)
        ->with(['variants' => function ($query) {
            $query->where('is_active', true);
        }])
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'options' => $category->variants->map(fn($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                ])->toArray(),
            ];
        });

    $draftProduct = auth()->user()->products()->latestDraft()->first();
    $productData = null;

    if ($draftProduct) {
        $variantRows = $draftProduct->variants()
            ->with('values.variant')
            ->get()
            ->map(function ($variant) {
                $row = [
                    'marked_price' => $variant->marked_price,
                    'buying_price' => $variant->buying_price,
                    'stock' => $variant->stock,
                    'sku' => $variant->sku,
                    'values' => [],
                ];

                foreach ($variant->values as $pvValue) {
                    $row['values'][$pvValue->variant->variant_category_id] = $pvValue->variant->value;
                }

                return $row;
            })->toArray();

        $productData = [
            'product_id' => $draftProduct->id,
            'id' => $draftProduct->id,
            'current_step' => $draftProduct->current_step,
            'max_step_completed' => $draftProduct->max_step_completed, // ✅ Added
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
        ];
    }

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
    ->with(['variants' => fn($query) => $query->where('is_active', true)])
    ->get();

    if ($variantCategories->isEmpty()) {
        $variantCategories = VariantCategory::where('default', true)
            ->with(['variants' => fn($query) => $query->where('is_active', true)])
            ->get();
    }

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

    $variantRows = $product->variants()->with('values.variant')->get()->map(function ($variant) {
        $row = [
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->buying_price,
            'stock' => $variant->stock,
            'sku' => $variant->sku,
            'values' => [],
        ];

        foreach ($variant->values as $pvValue) {
            $row['values'][$pvValue->variant->variant_category_id] = $pvValue->variant->value;
        }

        return $row;
    })->toArray();

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
        'max_step_completed' => $product->max_step_completed, // ✅ Added
        'product_code' => $product->product_code,
        'name' => $product->name,
        'category_id' => $product->category_id,
        'brand_id' => $product->brand_id,
        'unit_id' => $product->unit_id,
        'description' => $product->description,
        'features' => $product->features,
        'specifications' => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,
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

    // \Log::info('Product step submission started', [
    //     'step'       => $step,
    //     'product_id' => $request->input('product_id'),
    //     'user_id'    => $request->user()?->id,
    //     'data_keys'  => array_keys($data), // Log what data we received
    // ]);

    $product = null;
    if ($step > 1 && $request->filled('product_id')) {
        $product = Product::find($request->input('product_id'));
    }

    try {
        $product = $productService->createOrUpdateProductStep(
            $step,
            $data,
            $request->user(),
            $images,
            $product
        );

        if ($step === 4) {
            try{
                info('Dispatching RefreshProductCache job for product ID: '.$product->id);
                RefreshProductCache::dispatch($product)->delay(now()->addSeconds(5));
                info('RefreshProductCache job dispatched successfully for product ID: '.$product->id);
            }catch(\Exception $e){
                info('Error dispatching RefreshProductCache job for product ID: '.$product->id.' - '.$e->getMessage());
            }
            return redirect()->route('admin.products.index')
                ->with('success', "Product '{$product->name}' created successfully.");
        }

        return back()
            ->with('success', "Step {$step} completed successfully.")
            ->with('step', $product->current_step)
            ->with('product_id', $product->id);

    } catch (ValidationException $e) {
        // ✅ Log validation errors
        // \Log::warning("Validation failed on step {$step}", [
        //     'errors'     => $e->errors(),
        //     'input'      => $data,
        //     'product_id' => $product?->id,
        //     'user_id'    => $request->user()?->id,
        // ]);

        // Debug: log the errors before returning
        $errors = $e->errors();
        // \Log::debug('Validation errors to return:', [
        //     'error_count' => count($errors),
        //     'error_keys' => array_keys($errors),
        //     'first_error' => reset($errors),
        // ]);

        // Return validation errors to frontend
        $response = back()
            ->withErrors($errors)
            ->withInput()
            ->with([
                'step' => $step,
                'product_id' => $product?->id,
            ]);

        // Log the response we're about to send
        // \Log::debug('Returning validation response', [
        //     'session_errors' => session()->get('errors'),
        //     'session_flash' => session()->get('_flash'),
        // ]);
        info('Returning validation response with errors and input data '.$e);

        return $response;

    } catch (\Throwable $e) {
        // \Log::error("Product step {$step} failed", [
        //     'error'      => $e->getMessage(),
        //     'trace'      => $e->getTraceAsString(),
        //     'product_id' => $product?->id,
        //     'user_id'    => $request->user()?->id,
        //     'exception_type' => get_class($e), // Log what type of exception
        // ]);

        // Check if it might be a validation exception that wasn't caught
        if ($e instanceof ValidationException) {
            // \Log::warning('ValidationException caught in generic catch block!');
            // return back()
            //     ->withErrors($e->errors())
            //     ->withInput()
            //     ->with([
            //         'step' => $step,
            //         'product_id' => $product?->id,
            //     ]);
        }

        return back()
            ->with('error', "Something went wrong while processing the product: " . $e->getMessage())
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
        'primary_image_url' => $product->primary_image_url,
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

        // ✅ Use S3 URLs
        'images' => $product->images
            ->map(fn($img) => Storage::disk('s3')->url($img->image_path))
            ->toArray(),

        'image_urls' => $product->images
            ->map(fn($img) => Storage::disk('s3')->url($img->image_path))
            ->toArray(),

        'variants' => $product->productVariants->map(fn($variant) => [
            'id' => $variant->id,
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->buying_price,
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

    // Log the incoming data
    Log::info('Product status update received', [
        'product_id' => $product->id,
        'status_id' => $request->input('status_id'),
    ]);

    $product->update([
        'status_id' => $request->input('status_id'),
    ]);

    //cache when status updated to published
    $status=ProductStatus::find($request->input('status_id'));
    if ($status && $status->name =="published") {
        try {
            info('Dispatching RefreshProductCache job in updateStatus for product ID: '.$product->id);
            RefreshProductCache::dispatch($product)->delay(now()->addSeconds(5));
        }catch (\Throwable $e){
            info('Error dispatching RefreshProductCache job in updateStatus for product ID: '.$product->id.' - '.$e->getMessage());
        }
    }
    return redirect()
        ->back()
        ->with('success', 'Product status updated successfully.');
}


}
