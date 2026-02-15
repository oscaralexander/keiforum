@extends('mail.layouts.default')

@section('title', 'Activeer je account')

@section('content')
    <h1>Activeer je account</h1>
    <p>
        Hallo {{ $user->name }},
    </p>
    <p>
        Leuk dat je aanschuift bij Keiforum!
        Klik op de knop hieronder om je account te activeren.
    </p>
    <p>
        <a class="button" href="{{ route('activate-account', ['token' => $user->email_verification_token]) }}">Activeer mijn account</a>
    </p>
    <p>
        Tot snel!<br>
        {{ config('app.name') }}
    </p>
@endsection