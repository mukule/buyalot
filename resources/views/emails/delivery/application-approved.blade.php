@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;"/>
</div>

# Congratulations, {{ $user->name }}!

Your delivery partner application has been **approved**.

You can now log in to the delivery portal and start receiving delivery assignments.

@component('mail::panel')
**Email:** {{ $user->email }}
@endcomponent

@component('mail::button', ['url' => $loginUrl])
Login to delivery portal
@endcomponent

If you have any questions, reply to this email.

Thanks,<br>
The {{ $appName }} Team.

@endcomponent
