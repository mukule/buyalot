@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; max-width: 100%; height: auto;">
</div>

# Hello {{ $userName }},

Your submitted document **{{ $documentTypeName }}** has been reviewed.

@if ($status === 'approved')
Your document has **passed verification** successfully. Thank you for your cooperation.
@else
Your document **did not pass verification**.  
Reason: {{ $rejectionReason ?? 'No specific reason provided.' }}

Please resubmit a valid document at your earliest convenience to start selling.
@endif

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
