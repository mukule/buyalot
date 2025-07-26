@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;"/>
</div>

# Welcome to {{ $appName }}, {{ $application->first_name }}!

Thank you for submitting your seller application.

We have received your application and will review it shortly.

@if(!empty($application->company_legal_name))
**Business Name:** {{ $application->company_legal_name }}
@endif

If you have any questions, feel free to reply to this email.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
