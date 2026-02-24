@component('mail::message')
# Order ready for pickup

Your order **#{{ $order->order_code }}** is ready for collection.

**Pick up location:** {{ $pickupLocationName }}
@if($pickupAddress)
**Address:** {{ $pickupAddress }}
@endif
@if($pickupLocationDetail)
{{ $pickupLocationDetail }}
@endif

Please bring a valid ID and your order confirmation when collecting.

Thanks,
{{ config('app.name') }}
@endcomponent
