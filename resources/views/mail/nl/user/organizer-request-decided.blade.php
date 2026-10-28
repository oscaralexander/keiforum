@extends('mail.layouts.default')

@section('title', __($isApproved ? 'mail/user.organizer-approved.subject' : 'mail/user.organizer-denied.subject'))

@section('content')
    @if ($isApproved)
        <h1>Je mag activiteiten plaatsen 🎉</h1>
        <p>
            Hi {{ $user->emailName }},<br>
            <br>

            Goed nieuws: we hebben je aanvraag goedgekeurd. Je kunt vanaf nu activiteiten op de <a href="{{ route('agenda') }}" target="_blank">agenda</a> plaatsen.
        </p>
        <p>
            <a class="btn" href="{{ route('agenda.create') }}">Plaats een activiteit</a>
        </p>
    @else
        <h1>Je aanvraag is afgewezen</h1>
        <p>
            Hi {{ $user->emailName }},<br>
            <br>
            De beheerders hebben je aanvraag om activiteiten op de agenda te plaatsen helaas afgewezen.
            Heb je vragen? Stuur dan een berichtje naar <a href="mailto:mail@keiforum.nl">mail@keiforum.nl</a>.
        </p>
    @endif
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
