@extends('mail.layouts.default')

@section('title', 'Activeer je account')

@section('content')
    <h1>Activeer je account</h1>
    <p>
        Hi {{ $user->emailName }},<br />
        <br />
        Leuk dat je aanschuift bij Keiforum!
        Klik op de knop hieronder om je account te activeren.
    </p>
    <p>
        <a class="btn" href="{{ route('activate-account', ['token' => $user->email_verification_token]) }}">Activeer mijn account</a>
    </p>
    <p>
        Werkt de knop niet? Kopieer dan de volgende link en plak hem in je browser:<br />
        <a href="{{ route('activate-account', ['token' => $user->email_verification_token]) }}">{{ route('activate-account', ['token' => $user->email_verification_token]) }}</a>
    </p>
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection