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
@endsection
