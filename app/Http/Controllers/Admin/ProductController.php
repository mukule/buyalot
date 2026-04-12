<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Products\Product;
use App\Models\Unit;
use App\Models\VariantCategory;
use App\Models\Category;
use App\Services\SearchCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Products\ProductRestock;
use App\Models\Products\ProductStatus;
use App\Models\Products\ProductVariant;


class ProductController extends Controller
{


public function index(Request $request)
{
    $user = auth()->user();

    $query = Product::with([
        'primaryImage',
        'productVariants.values.variant',
        'category',
        'owner.roles',
        'owner.sellerApplication',
        'warranties'
    ])->orderBy('created_at', 'desc');

    // Only seller sees own products
    if ($user->hasRole('seller')) {
        $query->where('owner_type', 'seller')
              ->where('owner_id', $user->id);
    }

    // 🔹 Backend search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('product_code', 'like', "%{$search}%")
              ->orWhereHas('owner.sellerApplication', function ($q2) use ($search) {
                  $q2->where('company_legal_name', 'like', "%{$search}%");
              });
        });
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
                    'name' => $product->owner?->sellerApplication?->company_legal_name ?? $product->owner?->name,
                ],
                'warranties' => $product->warranties->map(fn($warranty) => [
                    'id' => $warranty->id,
                    'hashid' => $warranty->hashid,
                    'duration' => $warranty->duration,
                    'description' => $warranty->description,
                    'active' => $warranty->active,
                ]),
                'active_warranty' => $product->activeWarranty()?->only(['id','duration','description','active']) ?? null,
                'product_variants' => $product->productVariants->map(fn($v) => [
                    'id' => $v->id,
                    'display_name' => $v->display_name,
                    'stock' => $v->stock,
                ]),
            ];
        });

    // Statuses
    $statusesQuery = ProductStatus::orderBy('name');
//    if ($user->hasRole('seller')) {
//        $statusesQuery->whereIn('name', ['draft', 'submit', 'pause']);
//    }

    $statuses = $statusesQuery->get(['id', 'name', 'label', 'color_class'])
        ->map(fn($status) => [
            'id' => (int) $status->id,
            'name' => $status->name,
            'label' => $status->label,
            'color_class' => $status->color_class,
        ])->keyBy('id')->toArray();

    // Include current product statuses if missing
    foreach ($products as $product) {
        $sid = (int) ($product['status_id'] ?? 0);
        if ($sid && !isset($statuses[$sid])) {
            $currentStatus = ProductStatus::find($sid);
            if ($currentStatus) {
                $statuses[$currentStatus->id] = [
                    'id' => (int) $currentStatus->id,
                    'name' => $currentStatus->name,
                    'label' => $currentStatus->label,
                    'color_class' => $currentStatus->color_class,
                ];
            }
        }
    }

    $statuses = array_values($statuses);

    return Inertia::render('Admin/Products/Index', [
        'products' => $products,
        'productStatuses' => $statuses,
        'filters' => $request->only('search'), // pass search back to Vue
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


    $draftProduct = auth()->user()->products()->latestDraft()->first();

    $selectedCategoryId = $draftProduct?->category_id ?? null;


    $variantCategories = VariantCategory::when($selectedCategoryId, function ($query, $categoryId) {
        $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
    })
    ->with(['variants' => fn($q) => $q->where('is_active', true)])
    ->get();


    if ($variantCategories->isEmpty()) {
        $variantCategories = VariantCategory::where('default', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->get();
    }


    $variantRows = [];
    if ($draftProduct) {
        $variantRows = $draftProduct->variants()
            ->with(['values.variant', 'images' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(function ($variant) {
                $row = [
                    'id' => $variant->id,
                    'marked_price' => $variant->marked_price,
                    'buying_price' => $variant->selling_price,
                    'stock' => $variant->stock,
                    'sku' => $variant->sku,
                    'values' => [],
                    'images' => [],
                ];

                // Map variant attribute values
                foreach ($variant->values as $pvValue) {
                    $row['values'][$pvValue->variant->variant_category_id] = $pvValue->variant->value;
                }

                // Map variant images
                if ($variant->images) {
                    $row['images'] = $variant->images->map(function ($img) {
                        return [
                            'id' => $img->id,
                            'url' => $img->url,
                            'preview' => $img->url,
                            'is_primary' => $img->is_primary,
                            'sort_order' => $img->sort_order,
                            'file' => null,
                        ];
                    })->toArray();
                }

                return $row;
            })
            ->toArray();

        // Collect the actual variant category IDs used by saved variants
        $usedVariantCategoryIds = collect($variantRows)
            ->flatMap(fn($row) => array_keys($row['values']))
            ->unique()
            ->values();

        // Merge in any missing variant categories from the saved variants
        if ($usedVariantCategoryIds->isNotEmpty()) {
            $extraCategories = VariantCategory::whereIn('id', $usedVariantCategoryIds)
                ->with(['variants' => fn($q) => $q->where('is_active', true)])
                ->get();

            $existingIds = $variantCategories->pluck('id')->toArray();

            $variantCategories = $variantCategories->merge(
                $extraCategories->filter(fn($cat) => !in_array($cat->id, $existingIds))
            )->values();
        }
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
        'video_url' => $draftProduct->video_url,
        'package_size' => $draftProduct->package_size,
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

    /*
    |--------------------------------------------------------------------------
    | Load Variants With Images
    |--------------------------------------------------------------------------
    */
    $variants = $product->variants()
        ->with(['values.variant', 'images' => fn($q) => $q->orderBy('sort_order')])
        ->get();

   

    /*
    |--------------------------------------------------------------------------
    | Map Variants For Frontend
    |--------------------------------------------------------------------------
    */
    $variantRows = $variants->map(function ($variant) {

        $row = [
            'id' => $variant->id,
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->selling_price,
            'stock' => $variant->stock,
            'sku' => $variant->sku,
            'values' => [],
            'images' => [],
        ];

        foreach ($variant->values as $pvValue) {
            if (!$pvValue->variant) {
                \Log::warning('Null variant relation on value', [
                    'product_variant_id' => $variant->id,
                    'pv_value_id' => $pvValue->id,
                    'variant_id_fk' => $pvValue->variant_id ?? 'missing',
                ]);
                continue;
            }
            $row['values'][$pvValue->variant->variant_category_id] = $pvValue->variant->value;
        }

        if ($variant->images) {
            $row['images'] = $variant->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->url,
                    'preview' => $img->url,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                    'file' => null,
                ];
            })->values()->toArray();
        }

        return $row;

    })->values()->toArray();

   

    /*
    |--------------------------------------------------------------------------
    | Collect Used Variant Category IDs
    |--------------------------------------------------------------------------
    */
    $usedVariantCategoryIds = collect($variantRows)
        ->flatMap(fn($row) => array_keys($row['values']))
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values();

    

    /*
    |--------------------------------------------------------------------------
    | Merge Missing Categories Used By Saved Variants
    |--------------------------------------------------------------------------
    */
    if ($usedVariantCategoryIds->isNotEmpty()) {

        $extraCategories = VariantCategory::whereIn('id', $usedVariantCategoryIds)
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->get();

        $existingIds = $variantCategories->pluck('id')->toArray();

        $variantCategories = $variantCategories->merge(
            $extraCategories->filter(fn($cat) => !in_array($cat->id, $existingIds))
        )->values();

       
    }

    /*
    |--------------------------------------------------------------------------
    | Final Mapping For Frontend
    |--------------------------------------------------------------------------
    */
    $variantCategories = $variantCategories->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'options' => $category->variants->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
            ])->values()->toArray(),
        ];
    })->values();

   
    /*
    |--------------------------------------------------------------------------
    | Map Product Images
    |--------------------------------------------------------------------------
    */
    $images = collect($product->images ?? [])->map(function ($img) {
        return [
            'id' => $img->id,
            'file' => null,
            'preview' => $img->url ?? Storage::url($img->image_path ?? ''),
            'url' => $img->url ?? Storage::url($img->image_path ?? ''),
            'is_primary' => $img->is_primary ?? false,
        ];
    })->values()->toArray();

    /*
    |--------------------------------------------------------------------------
    | Prepare Product Data
    |--------------------------------------------------------------------------
    */
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
        'package_size' => $product->package_size,
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
        'title' => 'Edit Product',
        'breadcrumbs' => [
            ['label' => 'Products', 'url' => route('admin.products.index')],
            ['label' => 'Edit Product', 'url' => null],
        ],
    ]);
}


public function store(Request $request, ProductService $productService)
{
    $step = (int) $request->input('step');
    $data = $request->all();

    

    // --- MERGE FILES AND INPUT INTO CONSISTENT STRUCTURE FOR PRODUCT IMAGES ---
    $imagesInput = $request->input('images', []);
    $imagesFiles = $request->file('images', []);

    $images = [];
    foreach ($imagesInput as $index => $img) {
        $images[] = [
            'id'         => $img['id'] ?? null,
            'is_primary' => !empty($img['is_primary']),
            'file'       => $imagesFiles[$index]['file'] ?? null,
        ];
    }

    // --- MERGE VARIANT IMAGES (for Step 5) ---
    $variantImages = [];
    if ($step === 5) {
        // Get the variant_images metadata (without files)
        $variantImagesInput = $request->input('variant_images', []);

        

        foreach ($variantImagesInput as $index => $img) {
            // Skip if variant_id is missing
            if (empty($img['variant_id'])) {
               // \Log::warning('Skipping image: variant_id missing', ['image' => $img, 'index' => $index]);
                continue;
            }

            // Laravel stores file uploads separately - access via file() method
            $file = null;
            if ($request->hasFile("variant_images.{$index}.file")) {
                $file = $request->file("variant_images.{$index}.file");
               
            } else {
               // \Log::warning("No file found for variant image at index {$index}");
            }

            $variantImages[] = [
                'variant_id' => (int) $img['variant_id'],
                'id'         => !empty($img['id']) && is_numeric($img['id']) ? (int) $img['id'] : null,
                'is_primary' => !empty($img['is_primary']) && ($img['is_primary'] === '1' || $img['is_primary'] === 1 || $img['is_primary'] === true),
                'file'       => $file,
                'sort_order' => isset($img['sort_order']) ? (int) $img['sort_order'] : 0,
            ];
        }

        
    }

    // --- DETERMINE PRODUCT ---
    $productIdFromRequest = $request->input('product_id');
    $productIdFromSession = session('product_id');
    $productIdFromInput = $request->old('product_id');

    $product = null;

    if ($step > 1) {
        $idToFind = $productIdFromRequest ?? $productIdFromSession ?? $productIdFromInput;

        if ($idToFind) {
            $product = \App\Models\Products\Product::withoutGlobalScopes()->find($idToFind);
        } else {
           // \Log::warning("CRITICAL: Step {$step} initiated but NO Product ID found in Request or Session.");
        }

        // Authorization: ensure seller owns the product
        if ($product && $request->user()->user_type === 'seller') {
            if ((int) $product->owner_id !== (int) $request->user()->id) {
                \Log::error("SECURITY ALERT: Seller " . auth()->id() . " tried to access Product " . $product->id);
                abort(403, 'Unauthorized access to this product.');
            }
        }
    }

    try {
        // --- PARSE VARIANT ROWS IF JSON STRING (for Step 5) ---
        if ($step === 5 && isset($data['variant_rows']) && is_string($data['variant_rows'])) {
            $data['variant_rows'] = json_decode($data['variant_rows'], true);
            \Log::info("Decoded variant_rows from JSON", ['variant_rows' => $data['variant_rows']]);
        }

        // --- CHOOSE IMAGE PAYLOAD BASED ON STEP ---
        $imagesToSave = $step === 5 ? $variantImages : $images;

       

        $product = $productService->createOrUpdateProductStep(
            $step,
            $data,
            $request->user(),
            $imagesToSave,
            $product
        );

        // --- REDIRECT AFTER LAST STEP ---
        if ($step >= 4) {
            return redirect()->route('admin.products.index')
                ->with('success', "Product '{$product->name}' created successfully.");
        }

        return back()
            ->with('success', "Step {$step} completed successfully.")
            ->with('step', $product->current_step)
            ->with('product_id', $product->id);

    } catch (\Illuminate\Validation\ValidationException $e) {
      //  \Log::error("Step {$step} Validation Failed", ['errors' => $e->errors()]);
        return back()
            ->withErrors($e->errors())
            ->withInput()
            ->with([
                'step' => $step,
                'product_id' => $product?->id,
            ]);

    } catch (\Throwable $e) {
        // \Log::error("Step {$step} System Error", [
        //     'error' => $e->getMessage(),
        //     'trace' => $e->getTraceAsString(),
        // ]);
        return back()
            ->with('error', "System Error: " . $e->getMessage())
            ->with('step', $step)
            ->with('product_id', $product?->id);
    }
}




    public function destroy(Product $product)
{

    foreach ($product->images ?? [] as $image) {
        if (!empty($image['file_path']) && Storage::exists($image['file_path'])) {
            Storage::delete($image['file_path']);
        }
    }


    if (method_exists($product, 'variants')) {
        $product->variants()->delete();
    }

    if (method_exists($product, 'variant_rows')) {
        $product->variant_rows()->delete();
    }


    $product->delete();


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
        'productVariants.values.variant.category',
        'productVariants.images',
        'category.parent',
        'owner.roles',
        'brand',
        'unit',
    ]);

    $productData = [
        'id'                 => $product->id,
        'hashid'             => $product->hashid,
        'name'               => $product->name,
        'product_code'       => $product->product_code,
        'primary_image_url'  => $product->primary_image_url,
        'stock'              => $product->productVariants->sum('stock'),
        'category_hierarchy' => $product->category ? $product->category->getHierarchy() : [],

        'owner' => $product->owner ? [
            'id'    => $product->owner->id,
            'name'  => $product->owner->name,
            'roles' => $product->owner->getRoleNames()->toArray(),
        ] : null,

        'brand' => $product->brand ? [
            'id'   => $product->brand->id,
            'name' => $product->brand->name,
        ] : null,

        'unit' => $product->unit ? [
            'id'   => $product->unit->id,
            'name' => $product->unit->name,
        ] : null,

        'features'         => $product->features,
        'description'      => $product->description,
        'specifications'   => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,

        'images'     => $product->image_urls,
        'image_urls' => $product->image_urls,

        'variants' => $product->productVariants->map(fn($variant) => [
            'id'           => $variant->id,
            'marked_price' => $variant->marked_price,
            'buying_price' => $variant->selling_price,
            'stock'        => $variant->stock,
            'sku'          => $variant->sku,
            'values'       => $variant->values->map(fn($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value'               => $v->variant->value,
            ]),
            'images' => $variant->images->map(fn($img) => [
                'id'         => $img->id,
                'url'        => $img->url,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
                'alt_text'   => $img->alt_text,
            ]),
        ]),

        'variant_attributes' => $product->productVariants
            ->flatMap(fn($variant) => $variant->values)
            ->groupBy(fn($v) => $v->variant->category->name)
            ->map(fn($group, $categoryName) => [
                'name'    => $categoryName,
                'options' => $group->map(fn($v) => $v->variant->value)->unique()->values(),
            ])
            ->values(),

        'variant_map' => $product->productVariants
            ->keyBy(fn($variant) => $variant->values
                ->sortBy(fn($v) => $v->variant->variant_category_id)
                ->map(fn($v) => $v->variant->value)
                ->join('|')
            )
            ->map(fn($variant) => [
                'id'           => $variant->id,
                'marked_price' => $variant->marked_price,
                'buying_price' => $variant->selling_price,
                'stock'        => $variant->stock,
                'sku'          => $variant->sku,
                'images'       => $variant->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->url,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
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


    public function restockVariants(Request $request, Product $product)
    {
        $request->validate([
            'variants' => ['required', 'array'],
            'variants.*.id' => ['required', 'integer', 'exists:product_variants,id'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $variantIds = $product->productVariants->pluck('id')->toArray();
        $restocked = 0;

        foreach ($request->variants as $item) {
            $variantId = (int) $item['id'];
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0 || !in_array($variantId, $variantIds, true)) {
                continue;
            }

            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->first();

            if (!$variant) {
                continue;
            }

            $variant->increment('stock', $quantity);

            ProductRestock::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'note' => $request->note,
                'restocked_by' => auth()->id(),
            ]);

            $restocked += $quantity;
        }

        if ($restocked === 0) {
            return back()->withErrors([
                'variants' => ['Please enter at least one quantity to restock.'],
            ]);
        }

        return back()->with('success', "Restocked {$restocked} unit(s) successfully.");
    }

    public function restockHistory(Product $product)
    {
        $product->load(['primaryImage', 'productVariants.values.variant']);

        $restocks = ProductRestock::where('product_id', $product->id)
            ->with(['user', 'productVariant.values.variant'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'variant_display_name' => $r->productVariant?->display_name ?? 'Unknown',
                'quantity' => $r->quantity,
                'note' => $r->note,
                'restocked_by' => $r->user?->name ?? 'Unknown',
                'restocked_at' => $r->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Products/RestockHistory', [
            'product' => [
                'id' => $product->id,
                'hashid' => $product->hashid,
                'name' => $product->name,
                'product_code' => $product->product_code,
                'primary_image_url' => $product->primary_image_url,
            ],
            'restocks' => $restocks,
        ]);
    }
}
