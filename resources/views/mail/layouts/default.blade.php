<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>@yield('title', config('app.name'))</title>
        <style>
            body {
                background-color: #fcf9f6;
                margin: 0;
            }

            .mail {
                background-color: #fcf9f6;
                border-radius: 0.5rem;
                color: #666462;
                font-family: system-ui, sans-serif;
                font-size: 16px;
                line-height: 1.5;
                margin-bottom: 0.75rem;
                padding: 32px 16px;
            }

            .mail__wrapper {
                margin: 0 auto;
                max-width: 600px;
            }

            h1 {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 1.5rem;
                color: #c93020;
            }

            p {
                margin: 0 0 1.5rem 0;
            }
            
            a {
                color: #c93020;
            }

            h2 {
                color: #191919;
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 0.75rem 0;
            }

            table.topics {
                border-collapse: collapse;
                margin: 0 0 1.5rem 0;
            }

            table.topics td {
                padding: 0 0 0.75rem 0;
                vertical-align: middle;
            }

            table.topics td.topics__avatar {
                padding-right: 12px;
                width: 32px;
            }

            table.topics img {
                border-radius: 50%;
                display: block;
                height: 32px;
                width: 32px;
            }

            table.topics a {
                font-weight: 600;
            }

            table.topics small {
                color: #979594;
                font-size: 14px;
            }

            p.footer {
                color: #979594;
                font-size: 13px;
                margin: 2rem 0 0 0;
            }

            a.btn {
                background: #c93020;
                border-radius: 0.5rem;
                display: inline-block;
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                line-height: 1.5;
                text-decoration: none;
                padding: 0.5rem 1.25rem;
            }
        </style>
    </head>
    <body bgcolor="#fcf9f6" style="background-color: #fcf9f6; margin: 0;">
        <div class="mail" style="background-color: #fcf9f6;">
            <div class="mail__wrapper">
                @yield('content')
            </div>
        </div>
    </body>
</html>

