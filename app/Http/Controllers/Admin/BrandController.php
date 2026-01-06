<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshBrandCache;
use App\Models\Brand;
use App\Services\SearchCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        return Inertia::render('Admin/Brands/Create');
    }

    public function edit(Brand $brand)
    {
        return Inertia::render('Admin/Brands/Edit', [
            'brand' => $brand,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'boolean',
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
       // Log::info('Brand created successfully', ['id' => $brand->id]);

        // Dispatch job after update
        RefreshBrandCache::dispatch($brand)->delay(now()->addSeconds(5));
        return redirect()->route('admin.brands.index')->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'logo_path' => 'nullable|image|max:2048',
        ]);

        Log::info('Validated data:', $validated);

        $slug = Str::slug($validated['name']);
        if ($slug !== $brand->slug && Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
            $slug = $slug . '-' . uniqid();
        }

        $brand->fill([
            'name' => $validated['name'],
            'slug' => $slug,
            'active' => $validated['active'] ?? true,
        ]);

        if ($request->hasFile('logo_path')) {
            if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $brand->logo_path = $this->optimizeAndStoreImage($request->file('logo_path'));
        }

        $brand->save();
        // Dispatch job after update
        RefreshBrandCache::dispatch($brand)->delay(now()->addSeconds(5));

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();
        // Remove brand from cache
        $cache = SearchCacheService::get();
        // Remove brand entry
        $brandId = $brand->id;
        $cache['brands'] = array_filter($cache['brands'] ?? [], fn($b) => $b['id'] !== $brandId);

        // Remove all products under this brand
        $cache['products'] = array_filter($cache['products'] ?? [], fn($p) => $p['brand_id'] !== $brandId);
        // Remove variants of deleted products
        $cache['variants'] = array_filter($cache['variants'] ?? [], function ($v) use ($cache) {
            $productIds = array_column($cache['products'] ?? [], 'id');
            return !in_array($v['product_id'], $productIds);
        });

        Cache::put(SearchCacheService::CACHE_KEY, $cache, now()->addMonths(6));

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully.');
    }



protected function optimizeAndStoreImage($file): string
{
    $manager = new ImageManager(new Driver());

    $directory = 'brands';
    Storage::disk('public')->makeDirectory($directory);

    $image = $manager->read($file)
        ->scaleDown(600)
        ->toWebp(75);

    $filename = $directory . '/' . Str::uuid() . '.webp';

    $image->save(storage_path('app/public/' . $filename));

    // ✅ RETURN RELATIVE PATH
    return $filename;
}




}
