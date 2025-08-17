<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Traits\HasPermissionCheck;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    use HasPermissionCheck;
public function index()
{
//    $permissionCheck = $this->checkPermissionOrFail('view-dashboard');
//    if ($permissionCheck) {
//        return $permissionCheck;
//    }
    $categories = Category::with('subcategories')->get();
    $brands = Brand::all();

    $productsByCategory = $categories->mapWithKeys(function ($category) {
        $products = Product::with(['brand', 'primaryImage'])
            ->whereHas('subcategory', function ($query) use ($category) {
                $query->where('category_id', $category->id);
            })
            ->take(12)
            ->get();

        return [$category->id => $products];
    });


    return Inertia::render('Frontend/Index', [
        'title'              => 'Online Shopping Store',
        'categories'         => $categories,
        'brands'             => $brands,
        'productsByCategory' => $productsByCategory,
    ]);
}


public function productDetails(string $slug)
{
    $product = Product::with([
        'brand',
        'primaryImage',
        'subcategory.category',
        'images',
    ])
    ->where('slug', $slug)
    ->firstOrFail();


    $relatedProducts = Product::with('primaryImage')
        ->where('subcategory_id', $product->subcategory_id)
        ->where('id', '!=', $product->id)
        ->latest()
        ->take(10)
        ->get();

    return Inertia::render('Frontend/ProductDetail', [
        'product' => $product,
        'relatedProducts' => $relatedProducts,
        'title' => $product->name,
    ]);
}




}
