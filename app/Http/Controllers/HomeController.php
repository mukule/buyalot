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
    
  

public function index()
{
    $categories = Category::with('children')
        ->whereNull('parent_id')
        ->get();

    $brands = Brand::all();

    $productsByCategory = $categories->mapWithKeys(function ($category) {
        $categoryIds = $category->getAllCategoryIds();

        $products = Product::with([
                'brand',
                'primaryImage',
                'productVariants' => fn($q) => $q->orderBy('id'),
            ])
            ->whereIn('category_id', $categoryIds)
            ->where('status_id', 3) // ✅ Only active products
            ->take(12)
            ->get()
            ->map(function ($product) {
                $firstVariant = $product->productVariants->first();

                $product->regular_price = $firstVariant?->regular_price;
                $product->selling_price = $firstVariant?->selling_price;
                $product->discount = ($firstVariant && $firstVariant->regular_price > 0 && $firstVariant->regular_price > $firstVariant->selling_price)
                    ? round((($firstVariant->regular_price - $firstVariant->selling_price) / $firstVariant->regular_price) * 100)
                    : null;

                return $product;
            });

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
        'images',
        'productVariants.values.variant',
        'category.parent', // self-referencing category
    ])->where('slug', $slug)->firstOrFail();

    // Normalize variants with discount calculation
    $variants = $product->productVariants->map(fn($variant) => [
        'id' => $variant->id,
        'regular_price' => $variant->regular_price,
        'selling_price' => $variant->selling_price,
        'discount' => ($variant->regular_price > 0 && $variant->regular_price > $variant->selling_price)
            ? round((($variant->regular_price - $variant->selling_price) / $variant->regular_price) * 100)
            : null,
        'stock' => $variant->stock,
        'sku' => $variant->sku,
        'values' => $variant->values->map(fn($v) => [
            'variant_category_id' => $v->variant->variant_category_id,
            'value' => $v->variant->value,
        ]),
    ]);

    // Main product data normalized
    $productData = [
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->name,
        'primary_image_url' => $product->primary_image_url,
        'stock' => $product->productVariants->sum('stock'),
        'category_hierarchy' => $product->category ? $product->category->getHierarchy() : [],
        'brand' => $product->brand ? ['id' => $product->brand->id, 'name' => $product->brand->name] : null,
        'features' => $product->features,
        'description' => $product->description,
        'specifications' => $product->specifications,
        'whats_in_the_box' => $product->whats_in_the_box,
        'images' => $product->images->map(fn($img) => asset('storage/' . $img->image_path))->toArray(),
        'variants' => $variants,
    ];

    // Related products normalized
    $relatedProducts = Product::with('primaryImage')
        ->where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->latest()
        ->take(10)
        ->get()
        ->map(fn($p) => [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'primary_image_url' => $p->primary_image_url,
            'regular_price' => $p->productVariants->min('regular_price') ?? $p->price,
            'selling_price' => $p->productVariants->min('selling_price') ?? $p->discounted_price,
            'discount' => ($p->productVariants->min('regular_price') > 0 &&
                $p->productVariants->min('regular_price') > $p->productVariants->min('selling_price'))
                ? round((($p->productVariants->min('regular_price') - $p->productVariants->min('selling_price')) / $p->productVariants->min('regular_price')) * 100)
                : null,
        ]);

    return Inertia::render('Frontend/ProductDetail', [
        'product' => $productData,
        'relatedProducts' => $relatedProducts,
        'title' => $product->name,
    ]);
}




}
