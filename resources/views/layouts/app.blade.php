<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width,initial-scale=1.0,viewport-fit=cover" name="viewport">
        <meta content="#c93020" name="theme-color">
        <title>
            @if (Route::currentRouteName() === 'home')
                {{ config('app.name') }} - @lang('app.tagline')
            @else
                {{ $title ?? '' }} - {{ config('app.name') }}    
            @endif
        </title>
        <link href="/favicon.svg" rel="icon" sizes="any" type="image/svg+xml">
        <link href="{{ asset('apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180">
        <link href="{{ asset('google-touch-icon.png') }}" rel="google-touch-icon" sizes="180x180">
        @stack('meta')
        <meta content="@lang('app.description')" name="description">
        <meta content="@lang('app.description')" property="og:description">
        <meta content="{{ asset('assets/img/og-image-1.png') }}" property="og:image">
        <meta content="nl_NL" property="og:locale">
        <meta content="{{ config('app.name') }}" property="og:site_name">
        <meta content="{{ $title ?? config('app.name') }}" property="og:title">
        <meta content="website" property="og:type">
        <meta content="{{ url()->current() }}" property="og:url">
        @stack('schema')
        @stack('scripts.head')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @stack('scripts.auth')
        @livewireStyles
    </head>
    <body>
        <x-nav />
        <div @class(['page', $pageClass ?? null])>
            <main class="page__main">
                <div class="wrapper">
                    {{ $slot }}
                </div>
            </main>
            <x-footer />
        </div>
        @stack('scripts.body')
        @livewire('wire-elements-modal')
        @livewireScripts
    </body>
</html>
