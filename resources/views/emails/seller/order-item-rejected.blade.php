@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;" />
</div>

# Hello {{ $orderItem->seller?->company_legal_name ?? 'Vendor' }},

We regret to inform you that an item from an order has been **rejected** after review at our dispatch center.

### Item Details:
- **Product:** {{ $orderItem->productVariant?->product?->name ?? 'N/A' }}
- **Variant:** {{ $orderItem->productVariant?->display_name ?? 'N/A' }}
- **Quantity:** {{ $orderItem->quantity }}

### Reason for Rejection:
@component('mail::panel')
{{ $reason }}
@endcomponent

Please address the issues mentioned and contact our support team if you have any questions.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
