<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width,initial-scale=1.0,viewport-fit=cover" name="viewport">
        <meta content="#c93020" name="theme-color">
        @stack('meta')
        <title>{{ $title ?? '' }} - {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Parkinsans:wght@600&display=swap" rel="stylesheet">
        <link href="/favicon.svg" rel="icon" sizes="any" type="image/svg+xml">
        <link href="{{ asset('apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180">
        <link href="{{ asset('google-touch-icon.png') }}" rel="google-touch-icon" sizes="180x180">
        <meta property="og:description" content="De online huiskamer van Amersfoort.">
        <meta property="og:image" content="{{ asset('assets/img/og-image-1.png') }}">
        <meta property="og:locale" content="nl_NL">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        @stack('schema')
        @stack('scripts.head')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @auth
            @vite(['resources/js/auth.js'])
        @endauth
        @livewireStyles
    </head>
    <body>
        <div class="page page--simple">
            <div class="page__logo">
                <a href="{{ route('home') }}" class="logo">
                    <span class="logo__icon"></span>
                    <span class="logo__name">Keiforum</span>
                </a>
            </div>
            <div class="page__visual">
                @php
                    $visualNumber = rand(1, 5);
                @endphp
                <img alt="" src="{{ asset('assets/img/visuals/' . $visualNumber . '.webp') }}">
                <a class="page__visual-credit" href="https://www.albertdros.com/netherlands" target="_blank">Foto: Albert Dros</a>
            </div>
            <main class="page__main">
                <div class="page__main-inner">
                    <div class="wrapper wrapper--narrow">
                        {{ $slot }}
                    </div>
                </div>
                <x-footer />
            </main>
        </div>
        @stack('scripts.body')
        @livewire('wire-elements-modal')
        @livewireScripts
    </body>
</html>
