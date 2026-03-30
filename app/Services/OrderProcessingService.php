<?php

namespace App\Services;

use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Cart\Cart;
use App\Models\CheckoutSession;
use App\Models\Customer\CustomerAddress;
use App\Services\CartReservationService;
use App\Services\ShippingService;
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
        $amounts = $this->calculateTotals($cart, $data['shipping_amount'] ?? 0, $data['shipping_address_id'] ?? null);

        // Detect retry: if this cart already has live reservations the customer
        // has previously attempted payment and the window is still open.
        $isRetry = $this->reservationService->hasActiveReservations($cart->id);
        $ttl = $isRetry ? CartReservationService::TTL_RETRY : CartReservationService::TTL_INITIAL;

        $session = $this->updateOrCreateSession($cart, $amounts, $data);

        $this->reserveStock($cart, $ttl);

        $paymentInit = null;
        if ($data['payment_provider'] === 'mpesa') {
            $paymentInit = $this->initializeMpesa($session, $amounts['total'], $data['phone']);
        }
        if ($data['payment_provider'] === 'cod') {
            $codMaxAmount = $this->getCodMaxAmountForAddress($data['shipping_address_id'] ?? null);
            if ($codMaxAmount !== null && $amounts['total'] > $codMaxAmount) {
                throw new \Exception("Pay on Delivery is only available for orders up to KSh " . number_format($codMaxAmount) . ". Your order total is KSh " . number_format($amounts['total']) . ". Please use M-Pesa for orders over KSh " . number_format($codMaxAmount) . ".");
            }
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

    protected function calculateTotals($cart, $shipping, $addressId = null)
    {
        $totalItemsPrice = $cart->items->sum(fn($i) => ($i->unit_price ?? 0) * $i->quantity);
        $shippingRounded = max(0, (int) round((float) $shipping));

        // Server-side: enforce max_shipping_fee cap so no matter what the client
        // sends, the charge never exceeds the configured maximum for the zone.
        if ($shippingRounded > 0 && $addressId) {
            $shippingRounded = $this->applyMaxShippingFeeCap($shippingRounded, (int) $addressId);
        }

        $total = (int) round($totalItemsPrice + $shippingRounded);

        return [
            'cart'     => (int) round($totalItemsPrice),
            'discount' => 0, // In this model, unit_price is already discounted
            'shipping' => $shippingRounded,
            'total'    => $total
        ];
    }

    /**
     * Cap the computed shipping fee against the max_shipping_fee configured on the
     * shipping rate that applies to the delivery address's zone.
     *
     * For home delivery the effective cap is max(max_shipping_fee, door_fallback_price)
     * so the cap never undercuts the configured minimum fallback amount.
     * For pickup the plain max_shipping_fee cap is applied.
     */
    private function applyMaxShippingFeeCap(int $shipping, int $addressId): int
    {
        $address = CustomerAddress::with('region.zone')->find($addressId);
        if (!$address || !$address->region) {
            return $shipping;
        }

        $zoneId = $address->region->zone_id ?? $address->region->zone?->id;
        $rate = app(ShippingService::class)->getRateForZone($zoneId);

        if (!$rate) {
            return $shipping;
        }

        $isHomeDelivery = !$address->pickup_warehouse_id;

        return $isHomeDelivery
            ? (int) round($rate->applyHomeDeliveryCap($shipping))
            : (int) round($rate->applyCap($shipping));
    }

    protected function reserveStock($cart, int $ttlSeconds = CartReservationService::TTL_INITIAL)
    {
        foreach ($cart->items as $item) {
            $available = $this->reservationService->availableForCart($item->product_variant_id, $cart->id);
            if ($item->quantity > $available) {
                throw new \Exception("Items in your cart became unavailable.");
            }
            $this->reservationService->reserve($cart->id, $item->product_variant_id, $item->quantity, $ttlSeconds);
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

    /**
     * Get COD minimum amount from shipping rate for the delivery address.
     * Applies to both pickup point and home delivery: zone is resolved from address's region.
     * Returns null if no restriction (COD available for any amount).
     */
    protected function getCodMaxAmountForAddress(?int $addressId): ?float
    {
        if (!$addressId) {
            return null;
        }
        $address = CustomerAddress::with('region.zone')->find($addressId);
        if (!$address || !$address->region) {
            return null;
        }
        $zoneId = $address->region->zone_id ?? $address->region->zone?->id;
        $rate = app(ShippingService::class)->getRateForZone($zoneId);
        $max = $rate?->cod_max_amount;
        return $max !== null ? (float) $max : null;
    }

    protected function updateOrCreateSession($cart, $amounts, $data)
    {
        $session = CheckoutSession::firstOrNew(['cart_id' => $cart->id]);
        $session->ref_num = $session->ref_num ?? CheckoutSession::generateRefNum();
        $session->fill([
            'customer_id'       => $data['customer_id'] ?? auth()->user()?->customer?->id,
            'cart_amount'       => $amounts['cart'],
            'discount_amount'   => $amounts['discount'],
            'shipping_amount'   => $amounts['shipping'],
            'amount'            => $amounts['total'],
            'currency'          => $cart->currency ?? 'KES',
            'status'            => 'pending',
            'delivery_method'   => $data['delivery_method'] ?? null,
            'shipping_address_id' => isset($data['shipping_address_id']) ? (int) $data['shipping_address_id'] : null,
            'billing_address_id'  => isset($data['billing_address_id'])  ? (int) $data['billing_address_id']  : null,
        ])->save();

        return $session;
    }
}
