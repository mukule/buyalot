<?php

namespace App\Services;

use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Cart\Cart;
use App\Models\CheckoutSession;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    public function __construct(
        protected CartReservationService $reservationService,
        protected PaymentService $paymentService
    ) {}

    /**
     * @throws \Exception
     */
    public function process(int $cartId, array $data)
    {
        $cart = Cart::with(['items.productVariant.product'])->findOrFail($cartId);
        $amounts = $this->calculateTotals($cart, $data['shipping_amount'] ?? 0);

        $session = $this->updateOrCreateSession($cart, $amounts, $data);

        $this->reserveStock($cart);

        $paymentInit = null;
        if ($data['payment_provider'] === 'mpesa') {
            $paymentInit = $this->initializeMpesa($session, $amounts['total'], $data['phone']);
        }
        if ($data['payment_provider'] === 'cod') {
            $checkoutSession = CheckoutSession::where('cart_id', $cartId)->first();
            // Update checkout session status first
            if ($checkoutSession) {
                $checkoutSession->status = 'completed';
                $checkoutSession->save();
            }
            // Create order from completed checkout session
            if ($checkoutSession) {
                $shippingAddressId = $data['shipping_address_id'] ?? null;
                $billingAddressId = $data['billing_address_id'] ?? null;
                $order = app(\App\Services\OrderPlacementService::class)
                    ->placeOrder(
                        $checkoutSession->id,
                        $amounts['total'],
                        'cod',
                        'CASH ON DELIVERY',
                        $shippingAddressId ? (int) $shippingAddressId : null,
                        $billingAddressId ? (int) $billingAddressId : null
                    );
            }
        }

        return [$session, $paymentInit];
    }

    protected function calculateTotals($cart, $shipping)
    {
        $cartAmount = $cart->items->sum(fn($i) => ($i->marked_price ?? 0) * $i->quantity);
        $discount = $cart->items->sum(fn($i) => ($i->discount_amount ?? 0) * $i->quantity);
        $total = round($cartAmount + $shipping - $discount, 2);

        return ['cart' => $cartAmount, 'discount' => $discount, 'shipping' => $shipping, 'total' => $total];
    }

    protected function reserveStock($cart)
    {
        foreach ($cart->items as $item) {
            $available = $this->reservationService->availableForCart($item->product_variant_id, $cart->id);
            if ($item->quantity > $available) {
                throw new \Exception("Items in your cart became unavailable.");
            }
            $this->reservationService->reserve($cart->id, $item->product_variant_id, $item->quantity, 60);
        }
    }

    protected function initializeMpesa($session, $total, $phone)
    {
        try {
            $paymentRequest = new PaymentRequest(
                provider: 'mpesa',
                method: 'stk_push',
                amount: $total,
                currency: $session->currency,
                phone: $phone,
                metadata: ['checkout_session_ref' => $session->ref_num],
            );

            $mpesaLog = $this->paymentService->getOrCreateMpesaRequest($session, $paymentRequest);
            $init = $this->paymentService->initializePayment($mpesaLog, $paymentRequest);

            if (!$init->success) throw new \Exception($init->message);
            return $init;

        } catch (\Exception $e) {
            $this->reservationService->releaseAllForCart($session->cart_id);
            throw $e;
        }
    }

    protected function updateOrCreateSession($cart, $amounts, $data)
    {
        $session = CheckoutSession::firstOrNew(['cart_id' => $cart->id]);
        $session->ref_num = $session->ref_num ?? CheckoutSession::generateRefNum();
        $session->fill([
            'customer_id' => $data['customer_id'] ?? auth()->user()?->customer?->id,
            'cart_amount' => $amounts['cart'],
            'discount_amount' => $amounts['discount'],
            'shipping_amount' => $amounts['shipping'],
            'amount' => $amounts['total'],
            'currency' => $cart->currency ?? 'KES',
            'status' => 'pending',
        ])->save();

        return $session;
    }
}
