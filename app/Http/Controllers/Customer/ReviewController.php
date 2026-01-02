<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * Display the customer's review history.
     */
    public function index()
    {
        $userId = Auth::id();

        $reviews = ProductReview::with(['product.primaryImage'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function ($review) {
                $product = $review->product;
                $primaryImageUrl = $product?->primaryImage
                    ? Storage::disk('s3')->url($product->primaryImage->image_path)
                    : null;

                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'status' => $review->getStatusLabelAttribute(),
                    'created_at' => $review->created_at->format('M d, Y'),
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name,
                        'slug' => $product?->slug,
                        'thumbnail' => $primaryImageUrl,
                    ],
                ];
            });

        return Inertia::render('Customer/Reviews', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Display products pending review.
     */
    public function pending()
    {
        $userId = Auth::id();
        $customer = Auth::user()->customer;

        if (!$customer) {
            return Inertia::render('Customer/PendingReviews', [
                'products' => [],
            ]);
        }

        // Get all products from fulfilled orders
        $fulfilledOrders = Order::where('customer_id', $customer->id)
            ->whereIn('status', ['delivered', 'completed'])
            ->with(['orderItems.productVariant.product.primaryImage'])
            ->get();

        $productsToReview = collect();
        $reviewedProductIds = ProductReview::where('user_id', $userId)
            ->pluck('product_id')
            ->toArray();

        foreach ($fulfilledOrders as $order) {
            foreach ($order->orderItems as $item) {
                $product = $item->productVariant?->product;
                if ($product && !in_array($product->id, $reviewedProductIds)) {
                    // Avoid duplicates if same product bought multiple times
                    if (!$productsToReview->has($product->id)) {
                        $primaryImageUrl = $product->primaryImage
                            ? Storage::disk('s3')->url($product->primaryImage->image_path)
                            : null;

                        $productsToReview->put($product->id, [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'thumbnail' => $primaryImageUrl,
                            'order_date' => $order->created_at->format('M d, Y'),
                        ]);
                    }
                }
            }
        }

        return Inertia::render('Customer/PendingReviews', [
            'products' => $productsToReview->values(),
        ]);
    }
}
