@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;" />
</div>

# Hello {{ $user->name }},

Your delivery partner account has been **suspended**. You will not be able to log in to the delivery portal or receive new delivery assignments until the suspension is lifted.

### Reason:
@component('mail::panel')
{{ $reason }}
@endcomponent

If you believe this is an error or would like to discuss, please contact us.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
