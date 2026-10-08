@extends('mail.layouts.default')

@section('title', __('mail/user.reset-password.subject'))

@php
    $resetUrl = route('reset-password', ['token' => $token, 'email' => $user->email]);
@endphp

@section('content')
    <h1>Wachtwoord herstellen</h1>
    <p>
        Hi {{ $user->emailName }},<br />
        <br />
        Je hebt aangegeven je wachtwoord te zijn vergeten.
        Klik op de knop hieronder om een nieuw wachtwoord in te stellen.
    </p>
    <p>
        <a class="btn" href="{{ $resetUrl }}">Kies een nieuw wachtwoord</a>
    </p>
    <p>
        Werkt de knop niet? Kopieer dan de volgende link en plak hem in je browser:<br />
        <a href="{{ $resetUrl }}" style="color: #c93020;">{{ $resetUrl }}</a>
    </p>
    <p>
        Deze link is {{ config('auth.passwords.users.expire') }} minuten geldig.
        Heb je dit niet zelf aangevraagd? Dan hoef je niets te doen en blijft je wachtwoord ongewijzigd.
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
