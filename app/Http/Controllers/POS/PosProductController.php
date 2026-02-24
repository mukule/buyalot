<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Services\SellerContext;
use Illuminate\Http\Request;

class PosProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();

        $query = Product::withoutGlobalScopes()
            ->with(['category', 'productVariants' => function ($q) {
                $q->with('values.variant.category');
            }])
            ->whereIn('status', [Product::STATUS_APPROVED, Product::STATUS_PENDING, 1]);

        if ($user && ! SellerContext::isAdmin($user)) {
            $relatedUserIds = SellerContext::relatedUserIds($user);
            $query->whereIn('owner_type', ['seller', 'vendor'])
                  ->whereIn('owner_id', $relatedUserIds);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('product_code', 'like', "%{$request->search}%")
                  ->orWhereHas('productVariants', function ($qv) use ($request) {
                      $qv->where('sku', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->paginate(32);

        $products->getCollection()->transform(function ($product) {
            $product->productVariants->each(function ($variant) {
                $variant->label = $variant->display_name;
            });
            return $product;
        });

        return response()->json($products);
    }

    public function categories()
    {
        $categories = Category::active()->get();
        $brands = Brand::where('active', true)->get();
        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
}
