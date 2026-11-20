@use('Livewire\Livewire')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-currency="{{ config('essentials.currency') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name') }}</title>
    {{--  The Nyumbani favicons, served by the package so every app inherits them. The SVG switches its own colours in dark mode, which browsers honour where they ignore media on icon links, and the .ico is the fallback  --}}
    <link rel="icon" type="image/svg+xml" href="{{ route('nyumbani.favicon', ['image' => 'favicon.svg']) }}">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="{{ route('nyumbani.favicon', ['image' => 'favicon.ico']) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('nyumbani.favicon', ['image' => 'apple-touch-icon.png']) }}">
    <link rel="manifest" href="{{ route('nyumbani.manifest') }}">
    <meta name="theme-color" content="{{ config('ui.branding.theme_color') }}">
    @if (class_exists(Livewire::class))
        @livewireStyles
    @endif
    {{--  The translations the app's own scripts read through window.__  --}}
    <script>
        window.__lang = @js(collect(config('ui.js_translations', []))->mapWithKeys(fn ($text) => [$text => __($text)]));
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="@yield('body-class', 'h-full bg-gray-100 text-sm text-gray-700 antialiased')">
    @yield('body')

    @if (class_exists(Livewire::class))
        @livewireScriptConfig
    @endif

    @include('nyumbani::partials.alerts')
    @stack('js')
</body>
</html>
