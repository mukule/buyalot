<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class CartController extends Controller
{
   


  
    public function index(Request $request)
{
    $cart = $this->getCart($request);

    // Eager load productVariant and product with primaryImage
    $cart->load('items.productVariant.product.primaryImage');

    // Each cart item will now automatically have a full URL available
    // via $item->productVariant->product->primaryImageUrl
    // no need to manually map asset paths here

    return Inertia::render('Frontend/Cart', [
        'cart' => $cart,
    ]);
}


    public function store(Request $request)
    {
        $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'           => 'required|integer|min:0', // allow 0 to remove
        ]);

        $variantId = $request->input('product_variant_id');
        $quantity  = (int) $request->input('quantity');

        $variant   = ProductVariant::with('product')->findOrFail($variantId);
        $cart      = $this->getCart($request);

        $cartItem = $cart->items()->where('product_variant_id', $variantId)->first();

        if ($quantity === 0) {
            if ($cartItem) {
                $cartItem->delete();
            }
            return redirect()->back()->with('success', "{$variant->product->name} removed from cart!");
        }

        $unitPrice      = $variant->selling_price; // or selling_price depending on logic
        $discountAmount = $variant->regular_price - $variant->selling_price;
        $totalPrice     = ($unitPrice - $discountAmount) * $quantity;

        if ($cartItem) {
            $cartItem->update([
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'discount_amount'=> $discountAmount,
                'total_price'    => $totalPrice,
            ]);
        } else {
            $cart->items()->create([
                'product_id'         => $variant->product_id,
                'seller_id'          => $variant->product->seller_id ?? null,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'discount_amount'    => $discountAmount,
                'total_price'        => $totalPrice,
            ]);
        }

        return redirect()->back()->with('success', "{$variant->product->name} cart updated!");
    }

    public function decrease(Request $request, CartItem $item)
    {
        $cart = $this->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($item->quantity > 1) {
            $item->decrement('quantity');
            $item->update([
                'total_price' => ($item->unit_price - $item->discount_amount) * $item->quantity,
            ]);
        } else {
            $item->delete();
        }

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }

    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = $this->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        $quantity = $request->input('quantity');

        if ($quantity === 0) {
            $item->delete();
        } else {
            $item->update([
                'quantity'    => $quantity,
                'total_price' => ($item->unit_price - $item->discount_amount) * $quantity,
            ]);
        }

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }

    public function destroy(Request $request, CartItem $item)
    {
        $cart = $this->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        $item->delete();

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request)
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();

        return redirect()->back()->with('success', 'Cart cleared.');
    }

    protected function getCart(Request $request): Cart
    {
        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            if ($guestToken = $request->cookie('cart_token')) {
                $guestCart = Cart::where('cart_token', $guestToken)->first();

                if ($guestCart && $guestCart->id !== $cart->id) {
                    DB::transaction(function () use ($cart, $guestCart) {
                        foreach ($guestCart->items as $item) {
                            $cart->items()->updateOrCreate(
                                ['product_variant_id' => $item->product_variant_id],
                                [
                                    'quantity'        => DB::raw('quantity + ' . $item->quantity),
                                    'unit_price'      => $item->unit_price,
                                    'discount_amount' => $item->discount_amount,
                                    'total_price'     => ($item->unit_price - $item->discount_amount) * $item->quantity,
                                    'product_id'      => $item->product_id,
                                    'seller_id'       => $item->seller_id,
                                ]
                            );
                        }

                        $guestCart->items()->delete();
                        $guestCart->delete();
                    });
                }
            }
        } else {
            $token = $request->cookie('cart_token') ?? Str::uuid()->toString();
            $cart  = Cart::firstOrCreate(['cart_token' => $token]);

            if (!$request->cookie('cart_token')) {
                cookie()->queue('cart_token', $token, 60 * 24 * 30); // 30 days
            }
        }

        return $cart;
    }
}
