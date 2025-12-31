@component('mail::message')
# Hi {{ $order->customer?->name ?? 'Customer' }},

Thank you for shopping with us! Your order **{{ $order->order_code }}** has been confirmed.

**Pickup / Delivery Address:**  
{{ $order->shippingAddress->label ?? 'N/A' }}  
{{ $order->shippingAddress->address_line_1 ?? '' }}

**Order Details:**

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_snapshot['name'] }} | {{ $item->quantity }} | KSh {{ number_format($item->total_price, 2) }} |
@endforeach
@endcomponent

**Delivery Fees:** KSh {{ number_format($order->shipping_amount, 2) }}  
**Discount:** KSh {{ number_format($order->discount_amount, 2) }}  
**TOTAL:** **KSh {{ number_format($order->total_amount, 2) }}**

**Payment Method:** {{ ucfirst($order->payment_method ?? 'N/A') }}  
**Payment Status:** {{ ucfirst($order->payment_status) }}

Thanks for shopping with us!  
{{ config('app.name') }}
@endcomponent
