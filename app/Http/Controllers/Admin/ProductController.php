<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\VariantCategory;
use App\Models\Category;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Inertia\Inertia;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;







class ProductController extends Controller
{
   
    public function index()
{
    $products = Product::with(['brand', 'subcategory', 'unit', 'primaryImage'])
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    return Inertia::render('Admin/Products/Index', [
        'products' => $products,
    ]);
}


public function create()
{
    $brands = Brand::where('active', true)->get();
    $categories = Category::where('active', true)->get();  
    $subcategories = Subcategory::where('active', true)->get();
    $units = Unit::where('active', true)->get();

    $variantCategories = VariantCategory::with(['variants' => function ($query) {
        $query->where('is_active', true);
    }])->get();

    return Inertia::render('Admin/Products/Create', [
        'brands' => $brands,
        'categories' => $categories,
        'subcategories' => $subcategories,
        'units' => $units,
        'variantCategories' => $variantCategories,
    ]);
}


public function store(StoreProductRequest $request, ProductService $productService)
{
    try {
        $data = $request->validated();
        Log::info('Validated product data', ['data' => $data]);

        $product = $productService->createProduct(
            $data,
            Auth::user(),
            $request->file('images')
        );

        if (!$product) {
            Log::error('Product creation returned null.');
            return back()->with('error', 'Failed to create the product.');
        }

        Log::info('Product created successfully', ['product_id' => $product->id]);

        // Attach variants if present
        if (!empty($data['variants'])) {
            $variantsPayload = json_decode($data['variants'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON for variants', ['json' => $data['variants']]);
            } elseif (is_array($variantsPayload)) {
                $variantIds = [];
                foreach ($variantsPayload as $vp) {
                    if (isset($vp['variant_ids']) && is_array($vp['variant_ids'])) {
                        $variantIds = array_merge($variantIds, $vp['variant_ids']);
                    }
                }
                Log::info('Syncing product variants', ['variant_ids' => $variantIds]);
                $product->variants()->sync($variantIds);
            }
        }

        // Handle variant categories (with new variants)
        if (!empty($data['variant_categories'])) {
            $variantCategoriesPayload = json_decode($data['variant_categories'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON for variant categories', ['json' => $data['variant_categories']]);
            } elseif (is_array($variantCategoriesPayload)) {
                foreach ($variantCategoriesPayload as $vc) {
                    if (isset($vc['id'], $vc['variants']) && is_array($vc['variants'])) {
                        foreach ($vc['variants'] as $variant) {
                            if (!empty($variant['value']) && empty($variant['id'])) {
                                Log::info('Creating new variant', ['category_id' => $vc['id'], 'value' => $variant['value']]);
                                Variant::create([
                                    'variant_category_id' => $vc['id'],
                                    'value' => $variant['value'],
                                    'is_active' => true,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        Log::info('Product creation process completed.');
        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');

    } catch (ValidationException $e) {
        Log::error('Validation failed for product submission.', [
            'errors' => $e->errors(),
            'input' => $request->all(),
        ]);
        throw $e;

    } catch (\Throwable $e) {
        Log::error('Unexpected error while creating product.', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return back()->with('error', 'Something went wrong while creating the product.');
    }
}

public function edit(Product $product)
{
    $brands = Brand::where('active', true)->get();
    $categories = Category::where('active', true)->get();
    $subcategories = Subcategory::where('active', true)->get();
    $units = Unit::where('active', true)->get();

    $variantCategories = VariantCategory::with(['variants' => function ($query) {
        $query->where('is_active', true);
    }])->get();

    
    $product->load([
        'brand',
        'subcategory',
        'unit',
        'images',
        'variants.category', 
    ]);

    return Inertia::render('Admin/Products/Edit', [
        'product' => $product,
        'brands' => $brands,
        'categories' => $categories,
        'subcategories' => $subcategories,
        'units' => $units,
        'variantCategories' => $variantCategories,
    ]);
}


public function update(UpdateProductRequest $request, Product $product)
{
    $data = $request->validated();

    DB::beginTransaction();

    try {
        // 1. Update basic product fields
        $product->update(Arr::only($data, [
            'name', 'description', 'features', 'specifications', 'whats_in_the_box',
            'meta_title', 'meta_description', 'meta_keywords',
            'brand_id', 'subcategory_id', 'unit_id', 'stock', 'price', 'discount'
        ]));

        // 2. Handle variants (your existing variant sync logic)
        // ...

        // 3. Image Handling - Complete Solution
        $uploadedImages = $request->file('images', []);
        $existingImageIds = $request->input('existing_images', []);
        $primaryIndex = (int) ($request->input('primary_image_index', -1));
        
        // 3a. Delete images that were removed
        $imagesToDelete = $product->images()
            ->whereNotIn('id', $existingImageIds)
            ->get();

        foreach ($imagesToDelete as $image) {
            if (Storage::exists($image->image_path)) {
                Storage::delete($image->image_path);
            }
            $image->delete();
        }

        // 3b. Process new uploads
        $newImageRecords = [];
        if (!empty($uploadedImages)) {
            foreach ($uploadedImages as $index => $image) {
                $path = $image->store('products/'.$product->id, 'public');
                
                $newImageRecords[] = [
                    'image_path' => $path,
                    'is_primary' => ($primaryIndex >= count($existingImageIds) && 
                                    $index === ($primaryIndex - count($existingImageIds))),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            if (!empty($newImageRecords)) {
                $product->images()->createMany($newImageRecords);
            }
        }

        
        if ($primaryIndex >= 0) {
            
            $product->images()->update(['is_primary' => false]);
            
           
            if ($primaryIndex < count($existingImageIds)) {
                // Existing image is primary
                $product->images()
                    ->where('id', $existingImageIds[$primaryIndex])
                    ->update(['is_primary' => true]);
            } elseif (!empty($newImageRecords)) {
                // New image is primary (already set during creation)
            }
        }

        DB::commit();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Product update failed', [
            'product_id' => $product->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return back()
            ->withInput()
            ->with('error', 'Failed to update product: ' . $e->getMessage());
    }
}


    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
