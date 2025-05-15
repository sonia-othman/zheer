<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
    
    <!-- Manual RTL CSS (optional) -->
    @if(in_array(app()->getLocale(), ['ar', 'ku']))
        @vite(['resources/css/rtl.css'])
    @endif
    
    <script>
        window.Laravel = {
            locale: "{{ app()->getLocale() }}",
            fallbackLocale: "{{ config('app.fallback_locale') }}",
            isRtl: {{ in_array(app()->getLocale(), ['ar', 'ku']) ? 'true' : 'false' }}
        };
    </script>
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>