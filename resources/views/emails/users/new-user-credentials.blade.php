@component('mail::message')

<div style="text-align:center; margin-bottom: 16px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 50px;">
    <h1 style="margin-top: 12px; font-size: 20px;">Welcome to {{ $appName }}</h1>
</div>

Hi {{ $user->name }},

An account has been created for you on {{ $appName }}.

Here are your login details:

- Email: {{ $user->email }}
- Temporary Password: **{{ $password }}**

For your security, please log in and change your password immediately.

@component('mail::button', ['url' => $loginUrl])
Log In
@endcomponent

If the button above doesn’t work, copy and paste this link into your browser:
{{ $loginUrl }}

Thanks,
{{ $appName }} Team

@endcomponent
