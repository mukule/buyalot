<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link rel="icon" href="/favicon.png" sizes="any" />
        <link rel="icon" href="/bianlina.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

        {{-- Default meta for pages without a `seo` prop (admin, account, checkout). Storefront pages
             pass `seo` and render their own tags through <SeoHead>, so the defaults are skipped
             to avoid duplicate description/canonical tags in the crawled HTML. --}}
        @unless (isset($page['props']['seo']))
        <title inertia>{{ config('app.name', 'Bianlina') }}</title>
        <meta inertia="description" name="description" content="Shop the best deals on {{ config('app.name', 'Bianlina') }} - Your preferred online store in Kenya.">
        <meta inertia="robots" name="robots" content="{{ request()->is('admin*', 'distributor*', 'seller*', 'warehouse*', 'cart*', 'checkout*', 'customer*', 'customers*', 'notifications*', 'payments*', 'login', 'register', 'password*', 'settings*', 'dashboard*', 'orders*') ? 'noindex, nofollow' : 'index, follow' }}">
        <link inertia="canonical" rel="canonical" href="{{ url()->current() }}">

        <meta inertia="og:type" property="og:type" content="website">
        <meta inertia="og:url" property="og:url" content="{{ url()->current() }}">
        <meta inertia="og:title" property="og:title" content="{{ config('app.name', 'Bianlina') }} | Online Shopping">
        <meta inertia="og:description" property="og:description" content="Quality products at the best prices. Fast delivery and secure payments.">
        <meta inertia="og:site_name" property="og:site_name" content="{{ config('app.name', 'Bianlina') }}">
        <meta inertia="og:image" property="og:image" content="{{ asset('preview.png') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="512">
        <meta property="og:image:height" content="512">
        <meta property="og:image:alt" content="{{ config('app.name', 'Bianlina') }} Logo">

        <meta inertia="twitter:card" name="twitter:card" content="summary">
        <meta inertia="twitter:title" name="twitter:title" content="{{ config('app.name', 'Bianlina') }}">
        <meta inertia="twitter:description" name="twitter:description" content="Quality products at the best prices. Fast delivery and secure payments.">
        <meta inertia="twitter:image" name="twitter:image" content="{{ asset('preview.png') }}">
        @endunless

        @if (config('seo.google_site_verification'))
            <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
        @endif
        @if (config('seo.bing_site_verification'))
            <meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">
        @endif

        {{-- Site-wide Organization + WebSite (sitelinks search box) structured data --}}
        @foreach (\App\Services\Seo\SeoMeta::siteGraph() as $graph)
            <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endforeach

        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
