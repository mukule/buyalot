@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;"/>
</div>

# Congratulations, {{ $user->name }}!

Your seller application has been **approved**.

You can now log in to your account using the following credentials to proceed with Verification:

@component('mail::panel')
**Email:** {{ $user->email }}  
**Password:** {{ $password }}
@endcomponent

@component('mail::button', ['url' => $loginUrl])
Login to your account
@endcomponent

Please change your password after logging in for security.

@if(!empty($application->company_legal_name))
**Business Name:** {{ $application->company_legal_name }}
@endif

If you have any questions or need assistance, feel free to reply to this email.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
