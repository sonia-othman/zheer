<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <!-- RTL Styles (loaded conditionally) -->
    @if(in_array(app()->getLocale(), ['ar', 'ku']))
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
    <script>
        window.Laravel = {
            locale: "{{ app()->getLocale() }}",
            fallbackLocale: "{{ config('app.fallback_locale') }}"
        };
    </script>
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>