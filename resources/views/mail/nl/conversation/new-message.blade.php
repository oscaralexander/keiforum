@extends('mail.layouts.default')

@section('title', __('mail/new_message.subject', ['user' => $message->user->username]))

@section('content')
    <h1>@lang('mail/new_message.heading', ['user' => $message->user->username])</h1>
    <p>
        {{ Str::limit($message->body_plain_text, 200) }}
    </p>
    <p>
        <a class="button" href="{{ route('messages', $message->conversation) }}">@lang('mail/new_message.cta')</a>
    </p>
@endsection
