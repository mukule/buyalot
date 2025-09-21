<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Services\FrontendProductService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    protected FrontendProductService $productService;

    public function __construct(FrontendProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Homepage
     */
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        $brands = Brand::all();

        $productsByCategory = $this->productService->getProductsGroupedByCategory($categories);

        return Inertia::render('Frontend/Index', [
            'title'              => 'Online Shopping Store',
            'categories'         => $categories,
            'brands'             => $brands,
            'productsByCategory' => $productsByCategory,
        ]);
    }

    /**
     * Product details page
     */
    public function productDetails(string $slug)
    {
        $product = Product::with([
            'brand',
            'primaryImage',
            'images',
            'productVariants.values.variant',
            'category.parent',
        ])->where('slug', $slug)->firstOrFail();

        $variants = $product->productVariants->map(fn($variant) => [
            'id'             => $variant->id,
            'regular_price'  => $variant->regular_price,
            'selling_price'  => $variant->selling_price,
            'discount'       => ($variant->regular_price > 0 && $variant->regular_price > $variant->selling_price)
                ? round((($variant->regular_price - $variant->selling_price) / $variant->regular_price) * 100)
                : null,
            'stock'          => $variant->stock,
            'sku'            => $variant->sku,
            'values'         => $variant->values->map(fn($v) => [
                'variant_category_id' => $v->variant->variant_category_id,
                'value'               => $v->variant->value,
            ]),
        ]);

        $productData = [
            'id'                => $product->id,
            'slug'              => $product->slug,
            'name'              => $product->name,
            'primary_image_url' => $product->primary_image_url,
            'stock'             => $product->productVariants->sum('stock'),
            'category_hierarchy'=> $product->category ? $product->category->getHierarchy() : [],
            'brand'             => $product->brand ? [
                'id'   => $product->brand->id,
                'name' => $product->brand->name,
            ] : null,
            'features'          => $product->features,
            'description'       => $product->description,
            'specifications'    => $product->specifications,
            'whats_in_the_box'  => $product->whats_in_the_box,
            'images'            => $product->images->map(fn($img) => asset('storage/' . $img->image_path))->toArray(),
            'variants'          => $variants,
        ];

        $relatedProducts = $this->productService->getRelatedProducts($product);

        $cartVariantIds = [];
        $cart = app(\App\Services\CartService::class)->getCart(request());
        if ($cart) {
            $cartVariantIds = $cart->items()->pluck('product_variant_id')->toArray();
        }

        return Inertia::render('Frontend/ProductDetail', [
            'product'         => $productData,
            'relatedProducts' => $relatedProducts,
            'cartVariantIds'  => $cartVariantIds,
            'title'           => $product->name,
        ]);
    }

    /**
     * Display products for a specific category (with pagination)
     */
    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        // Paginated products for category + descendants
        $products = $this->productService->getPaginatedProductsByCategory($category, 20);

        return Inertia::render('Frontend/Category', [
            'category'    => $category,
            'products'    => $products,
            'breadcrumbs' => $category->getHierarchy(),
            'title'       => $category->name,
        ]);
    }
}
