@component('mail::message')
# Delivery assignment

Hi {{ $deliveryUser->name }},

You have been assigned to deliver **Order #{{ $order->order_code }}**.

**Delivery note**

{!! nl2br(e($deliveryNoteSummary)) !!}

**Customer Delivery address**

@if($order->shippingAddress)
{{--{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}--}}
{{ $order->shippingAddress->address_line_1 }}
@if($order->shippingAddress->address_line_2){{ $order->shippingAddress->address_line_2 }}
@endif
{{ $order->shippingAddress->city }}@if($order->shippingAddress->postal_code), {{ $order->shippingAddress->postal_code }}@endif
{{ $order->shippingAddress->country_name ?? $order->shippingAddress->country_code ?? '' }}
{{--Phone: {{ $order->shippingAddress->phone ?? 'N/A' }}--}}
@else
Address to be confirmed.
@endif

{{--**Order total:** {{ $order->currency }} {{ number_format($order->total_amount, 2) }}--}}

Please log in to accept or reject this assignment and view full details.

@component('mail::button', ['url' => $loginUrl])
View delivery dashboard
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
