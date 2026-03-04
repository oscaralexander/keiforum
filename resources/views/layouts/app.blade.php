<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width,initial-scale=1.0,viewport-fit=cover" name="viewport">
        @stack('meta')
        <title>{{ $title ?? '' }} - {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Parkinsans:wght@600&display=swap" rel="stylesheet">
        @stack('schema')
        @stack('scripts.head')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @auth
            @vite(['resources/js/auth.js'])
        @endauth
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
