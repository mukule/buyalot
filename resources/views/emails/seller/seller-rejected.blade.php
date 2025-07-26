@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;" />
</div>

# Hello {{ $application->first_name }},

We regret to inform you that your seller application has been **rejected**.

### Reason:
@component('mail::panel')
{{ $reason }}
@endcomponent

@if(!empty($application->company_legal_name))
**Business Name:** {{ $application->company_legal_name }}
@endif

If you have questions or wish to reapply in the future, feel free to contact us.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
