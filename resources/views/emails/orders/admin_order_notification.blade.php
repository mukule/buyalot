@component('mail::message')
# New Order Received — {{ $order->order_code }}

A new order has been placed.

**Customer:**  
{{ $order->customer?->name }}  
{{ $order->customer?->email }}

**Order Summary**

- **Order Code:** {{ $order->order_code }}
- **Total Amount:** KSh {{ number_format($order->total_amount, 2) }}
- **Payment Status:** {{ ucfirst($order->payment_status) }}
- **Order Status:** {{ ucfirst($order->status) }}
- **Placed On:** {{ $order->created_at->format('d M Y, h:i A') }}

---

### **Items**

| Item | Qty | Price |
|------|-----:|------:|
@foreach ($order->items as $item)
| {{ $item->product_snapshot['name'] ?? 'Product' }} | {{ $item->quantity }} | KSh {{ number_format($item->total_price, 2) }} |
@endforeach

---

**Subtotal:** KSh {{ number_format($order->subtotal, 2) }}  
**Shipping:** KSh {{ number_format($order->shipping_amount, 2) }}  
**Discount:** KSh {{ number_format($order->discount_amount, 2) }}  
**TOTAL:** **KSh {{ number_format($order->total_amount, 2) }}**

---

@isset($order->shippingAddress)
**Shipping Address:**  
{{ $order->shippingAddress->label }}  
{{ $order->shippingAddress->address_line_1 }}
@endisset

@component('mail::button', ['url' => route('admin.orders.show', $order->id)])
View Order in Dashboard
@endcomponent

Thanks,  
{{ config('app.name') }} Admin System
@endcomponent
