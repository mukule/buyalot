@component('mail::message')
# Return raised for Order #{{ $order->order_code }}

A return has been raised for the above order.

**Reason:** {{ $reasonLabel }}
**Type:** {{ $isFullReturn ? 'Full return' : 'Partial return' }}

@if($orderReturn->reason_notes)
**Notes:** {{ $orderReturn->reason_notes }}
@endif

The delivery person or pickup point will bring the items back. Please receive the returned items at the dispatching warehouse when they arrive.

Thanks,
{{ config('app.name') }}
@endcomponent
