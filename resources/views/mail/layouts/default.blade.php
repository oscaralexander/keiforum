<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>@yield('title', config('app.name'))</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                line-height: 1.6;
                color: #1f2937;
                margin: 0;
                padding: 2rem;
                background-color: #f9fafb;
            }

            .mail-container {
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 0.75rem;
                padding: 2rem;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
                color: #111827;
            }

            p {
                margin: 0 0 1rem 0;
            }

            a.button {
                display: inline-block;
                background: #2563eb;
                color: #ffffff;
                text-decoration: none;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="mail-container">
            @yield('content')
        </div>
    </body>
</html>

