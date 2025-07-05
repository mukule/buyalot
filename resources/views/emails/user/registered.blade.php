@component('mail::message')

{{-- Logo --}}
<p style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto;">
</p>

 Welcome to {{ $appName }}, {{ $user->name }}!

We're thrilled to have you on board. Enjoy Shopping at **{{ $appName }}**.


Thanks,<br>
The {{ $appName }} Team.

@endcomponent
