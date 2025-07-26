@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;"/>
</div>

# Seller Document Submission Notification

{{ $application->company_legal_name ?? $application->first_name . ' ' . $application->last_name }} has submitted their 
{{ $submittedDocuments->pluck('documentType.name')->join(', ') }}.

Please log in to the admin dashboard to review.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
