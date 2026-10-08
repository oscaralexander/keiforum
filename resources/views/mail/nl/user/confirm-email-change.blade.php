@extends('mail.layouts.default')

@section('title', __('mail/user.confirm-email-change.subject'))

@section('content')
    <h1>Bevestig je nieuwe e-mailadres</h1>
    <p>
        Hi {{ $user->emailName }},<br />
        <br />
        Je hebt aangegeven dat je Keiforum-account voortaan dit e-mailadres moet gebruiken.
        Klik op de knop hieronder om dat te bevestigen.
    </p>
    <p>
        <a class="btn" href="{{ $confirmUrl }}">Bevestig mijn e-mailadres</a>
    </p>
    <p>
        Werkt de knop niet? Kopieer dan de volgende link en plak hem in je browser:<br />
        <a href="{{ $confirmUrl }}" style="color: #c93020;">{{ $confirmUrl }}</a>
    </p>
    <p>
        Deze link is {{ \App\Mail\ConfirmEmailChange::VALID_HOURS }} uur geldig.
        Heb je dit niet zelf aangevraagd? Dan hoef je niets te doen en blijft je e-mailadres ongewijzigd.
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
