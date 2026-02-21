<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link rel="icon" href="/favicon.png" sizes="any" />
        <link rel="icon" href="/bianlina.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

        <title inertia>{{ config('app.name', 'Bianlina') }}</title>
        <meta name="description" content="Shop the best deals on Bianlina - Your preferred online store.">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Bianlina') }} | Online Shopping">
        <meta property="og:description" content="Quality products at the best prices. Fast delivery and secure payments.">

        <meta property="og:image" content="{{ asset('preview.png') }}">
        <meta property="og:image:type" content="image/png">

        <meta property="og:image:width" content="512">
        <meta property="og:image:height" content="512">
        <meta property="og:image:alt" content="{{ config('app.name', 'Bianlina') }} Logo">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'Bianlina') }}">
        <meta name="twitter:description" content="Quality products at the best prices. Fast delivery and secure payments.">
        <meta name="twitter:image" content="{{ asset('preview.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
