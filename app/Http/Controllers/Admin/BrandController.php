<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Validation\Rule;



class BrandController extends Controller
{
  
    public function index()
{
    $brands = Brand::latest()->paginate(10); 

    return Inertia::render('Admin/Brands/Index', [
        'brands' => $brands,
    ]);
}


   
public function create()
{
    $categories = Category::select('id', 'name')->where('active', true)->get();

    $subcategories = Subcategory::select('id', 'name', 'category_id')
        ->where('active', true)
        ->get();

    return Inertia::render('Admin/Brands/Create', [
        'categories' => $categories,
        'subcategories' => $subcategories,
    ]);
}

public function edit(Brand $brand)
{
    $brand->load('subcategory.category'); // eager load subcategory and nested category

    $categories = Category::select('id', 'name')->where('active', true)->get();

    $subcategories = Subcategory::select('id', 'name', 'category_id')
        ->where('active', true)
        ->get();

    $brandWithCategory = $brand->toArray();
    $brandWithCategory['category_id'] = $brand->subcategory ? $brand->subcategory->category_id : null;

    return Inertia::render('Admin/Brands/Edit', [
        'brand' => $brandWithCategory,
        'categories' => $categories,
        'subcategories' => $subcategories,
    ]);
}





  public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'active' => 'boolean',
        'subcategory_id' => 'required|integer|exists:subcategories,id',
        'logo_path' => 'nullable|image|max:2048',
    ]);

    Log::info('Brand validated data', $validated);

    $slug = Str::slug($validated['name']);
    if (Brand::where('slug', $slug)->exists()) {
        $slug = $slug . '-' . uniqid();
        Log::info('Slug already exists, modified to', ['slug' => $slug]);
    }

    $brand = new Brand([
        'name' => $validated['name'],
        'slug' => $slug,
        'active' => $validated['active'] ?? true,
        'subcategory_id' => $validated['subcategory_id'],
    ]);

    if ($request->hasFile('logo_path')) {
        try {
            Log::info('Logo file received', ['original_name' => $request->file('logo_path')->getClientOriginalName()]);
            $brand->logo_path = $this->optimizeAndStoreImage($request->file('logo_path'));
            Log::info('Logo saved at', ['path' => $brand->logo_path]);
        } catch (\Exception $e) {
            Log::error('Image processing failed', ['error' => $e->getMessage()]);
        }
    } else {
        Log::info('No logo file uploaded.');
    }

    $brand->save();

    Log::info('Brand created successfully', ['id' => $brand->id]);

    return redirect()->route('admin.brands.index')->with('success', 'Brand created successfully.');
}


public function update(Request $request, Brand $brand)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'active' => 'boolean',
        'subcategory_id' => ['required', 'integer', Rule::exists('subcategories', 'id')->where('active', true)],
        'logo_path' => 'nullable|image|max:2048',
    ]);

    \Log::info('Validated data:', $validated);

    $slug = Str::slug($validated['name']);
    if ($slug !== $brand->slug && Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
        $slug = $slug . '-' . uniqid();
    }

    $brand->fill([
        'name' => $validated['name'],
        'slug' => $slug,
        'active' => $validated['active'] ?? true,
        'subcategory_id' => $validated['subcategory_id'],
    ]);

    if ($request->hasFile('logo_path')) {
        if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
            Storage::disk('public')->delete($brand->logo_path);
        }
        $brand->logo_path = $this->optimizeAndStoreImage($request->file('logo_path'));
    }

    $brand->save();

    return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully.');
}


    public function destroy(Brand $brand)
    {
        if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully.');
    }

    
  
    protected function optimizeAndStoreImage($file): string
{
    try {
        
        $manager = new ImageManager(new Driver());
        
        
        $directory = 'brands';
        Storage::disk('public')->makeDirectory($directory);
        
       
        $image = $manager->read($file)
            ->scaleDown(600)
            ->toWebp(75);

        $filename = $directory . '/' . Str::uuid() . '.webp';
        $image->save(storage_path('app/public/' . $filename));
        
        return $filename;
        
    } catch (\Exception $e) {
        Log::error('Image processing failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw new \Exception("Failed to process image: " . $e->getMessage());
    }
}

}
