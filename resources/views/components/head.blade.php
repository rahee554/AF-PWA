{{-- PWA Head Component --}}

{{-- PWA Meta Tags --}}
<meta name="application-name" content="{{ $options['name'] ?? config('app.name') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $options['short_name'] ?? $options['name'] ?? config('app.name') }}">
<meta name="description" content="{{ $options['description'] ?? 'A powerful Progressive Web Application' }}">
<meta name="format-detection" content="telephone=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="msapplication-config" content="none">
<meta name="msapplication-TileColor" content="{{ $options['theme_color'] ?? '#000000' }}">
<meta name="msapplication-tap-highlight" content="no">
<meta name="theme-color" content="{{ $options['theme_color'] ?? '#000000' }}">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">

{{-- Manifest --}}
<link rel="manifest" href="{{ route('af-pwa.manifest') }}">

{{-- Icons --}}
@if(isset($options['icons']) && is_array($options['icons']))
    @foreach($options['icons'] as $icon)
        @if(isset($icon['sizes']) && str_contains($icon['sizes'], '180x180'))
            <link rel="apple-touch-icon" href="{{ $icon['src'] }}" sizes="{{ $icon['sizes'] }}">
        @endif
        @if(isset($icon['type']) && $icon['type'] === 'image/x-icon')
            <link rel="icon" href="{{ $icon['src'] }}" type="{{ $icon['type'] }}">
        @endif
        @if(isset($icon['type']) && $icon['type'] === 'image/svg+xml')
            <link rel="icon" href="{{ $icon['src'] }}" type="{{ $icon['type'] }}">
        @endif
    @endforeach
@else
    {{-- Default icons --}}
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
@endif

{{-- Apple Startup Images (for better iOS support) --}}
<link rel="apple-touch-startup-image" href="/apple-splash-2048-2732.jpg" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-1668-2224.jpg" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-1536-2048.jpg" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-1125-2436.jpg" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-1242-2208.jpg" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-750-1334.jpg" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/apple-splash-640-1136.jpg" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">

{{-- CSS Assets --}}
<link rel="stylesheet" href="{{ asset('vendor/af-pwa/css/af-pwa.css') }}">

{{-- Preload critical PWA assets --}}
<link rel="preload" href="{{ route('af-pwa.service-worker') }}" as="script">
<link rel="preload" href="{{ asset('vendor/af-pwa/js/af-pwa.js') }}" as="script">

{{-- DNS Prefetch for PWA routes --}}
@if(isset($options['pwa_routes']) && is_array($options['pwa_routes']))
    @foreach($options['pwa_routes'] as $route)
        <link rel="prefetch" href="{{ url($route) }}">
    @endforeach
@endif
