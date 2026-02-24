@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;" />
</div>

# Hello {{ $application->name }},

We regret to inform you that your delivery partner application has **not been approved**.

### Reason:
@component('mail::panel')
{{ $reason }}
@endcomponent

You may correct the issues and reapply, or contact us if you have questions.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
