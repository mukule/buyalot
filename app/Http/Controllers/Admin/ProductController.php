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



public function store(Request $request, ImageService $imageService)
{
    try {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'specifications' => 'nullable|string',
            'whats_in_the_box' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'unit_id' => 'nullable|exists:units,id',
            'stock' => 'required|integer|min:0', 
            'variants' => 'nullable|string', 
            'variant_categories' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'primary_image_index' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);
    } catch (ValidationException $e) {
        Log::error('Validation failed for product submission.', [
            'errors' => $e->errors(),
            'input' => $request->all(),
        ]);
        throw $e;
    }

    $user = Auth::user();

    // Set ownership fields
    $data['owner_type'] = $user->getRoleNames()->first() ?? 'user';
    $data['owner_id'] = $user->id;

    // Generate meta fields if not provided
    $data['meta_title'] = $data['meta_title'] ?? $data['name'];
    $data['meta_description'] = $data['meta_description'] ?? substr(strip_tags($data['description'] ?? ''), 0, 160);
    $data['meta_keywords'] = $data['meta_keywords'] ?? implode(', ', explode(' ', $data['name']));

    // Create product including stock
    $product = Product::create([
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'features' => $data['features'] ?? null,
        'specifications' => $data['specifications'] ?? null,
        'whats_in_the_box' => $data['whats_in_the_box'] ?? null,
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'meta_keywords' => $data['meta_keywords'],
        'brand_id' => $data['brand_id'] ?? null,
        'subcategory_id' => $data['subcategory_id'] ?? null,
        'unit_id' => $data['unit_id'] ?? null,
        'stock' => $data['stock'], // ✅ saving stock
        'owner_type' => $data['owner_type'],
        'owner_id' => $data['owner_id'],
        'price' => $data['price'],
        'discount' => $data['discount'] ?? 0,
    ]);

    // Handle image uploads
    if ($request->hasFile('images')) {
        $primaryIndex = (int) $request->input('primary_image_index', -1);
        $images = $request->file('images');

        foreach ($images as $index => $image) {
            $basename = Str::slug($data['name']);
            $path = $imageService->optimizeAndStoreImage($image, 'products', $basename);

            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $primaryIndex === $index,
                'sort_order' => $index,
            ]);
        }
    }

    // Sync existing variant IDs
    if (!empty($data['variants'])) {
        $variantsPayload = json_decode($data['variants'], true);
        if (is_array($variantsPayload)) {
            $variantIds = [];
            foreach ($variantsPayload as $vp) {
                if (isset($vp['variant_ids']) && is_array($vp['variant_ids'])) {
                    $variantIds = array_merge($variantIds, $vp['variant_ids']);
                }
            }
            $product->variants()->sync($variantIds);
        }
    }

    // Create new variants from variant_categories
    if (!empty($data['variant_categories'])) {
        $variantCategoriesPayload = json_decode($data['variant_categories'], true);
        if (is_array($variantCategoriesPayload)) {
            foreach ($variantCategoriesPayload as $vc) {
                if (isset($vc['id'], $vc['variants']) && is_array($vc['variants'])) {
                    foreach ($vc['variants'] as $variant) {
                        if (!empty($variant['value']) && empty($variant['id'])) {
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

    return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
}



    public function edit(Product $product)
    {
        $brands = Brand::where('active', true)->get();
        $subcategories = Subcategory::where('active', true)->get();
        $units = Unit::where('active', true)->get();

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product->load('brand', 'subcategory', 'unit', 'images'),
            'brands' => $brands,
            'subcategories' => $subcategories,
            'units' => $units,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'specifications' => 'nullable|string',
            'whats_in_the_box' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'required|in:0,1',
            'owner_type' => 'required|string',
            'owner_id' => 'nullable|integer',
            'brand_id' => 'nullable|exists:brands,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
