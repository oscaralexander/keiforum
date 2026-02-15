<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width,initial-scale=1.0,viewport-fit=cover" name="viewport">
        @stack('meta')
        <title>{{ $title ?? 'Page Title' }}</title>
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
            <footer class="page__footer">
                <div class="wrapper">
                    <div class="page__footer-inner">
                        <p>© {{ now()->year }}, Keiforum. Met <span class="page__footer-heart">♥︎</span> gemaakt in Amersfoort.</p>
                        <ul class="meta">
                            <li class="meta__item">
                                <a href="/privacy">Privacy</a>
                            </li>
                            <li class="meta__item"><a href="/algemene-voorwaarden">Algemene voorwaarden</a></li>
                            <li class="meta__item"><a href="mailto:mail@keiforum.nl">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </footer>
        </div>
        @stack('scripts.body')
        @livewireScripts
    </body>
</html>
