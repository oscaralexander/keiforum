@extends('mail.layouts.default')

@section('title', __('mail/new_message.subject', ['user' => $msg->user->username]))

@section('content')
    <h1>{{ '@' . $msg->user->username }} heeft je een bericht gestuurd:</h1>
    <p style="background-color: #ffffff; padding: 1.5em; border: 1px solid #d9d6d3; border-radius: 1em;">
        {{ Str::limit($msg->body_plain_text, 200) }}
    </p>
    <p>
        <a class="btn" href="{{ route('conversations', $msg->conversation) }}">Ga naar berichten</a>
    </p>
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
