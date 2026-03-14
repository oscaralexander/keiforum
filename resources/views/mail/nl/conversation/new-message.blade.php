@extends('mail.layouts.default')

@section('title', __('mail/new_message.subject', ['user' => $message->user->username]))

@section('content')
    <h1>{{ $message->user->username }} heeft je een bericht gestuurd:</h1>
    <p>
        {{ Str::limit($message->body_plain_text, 200) }}
    </p>
    <p>
        <a class="btn" href="{{ route('conversations', $message->conversation) }}">Ga naar berichten</a>
    </p>
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
