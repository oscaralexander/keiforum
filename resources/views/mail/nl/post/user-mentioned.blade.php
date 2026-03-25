@extends('mail.layouts.default')

@section('title', __('mail/post/user_mentioned.subject', ['username' => $post->user->username]))

@section('content')
    <h1>@lang('mail/post/user_mentioned.subject', ['username' => $post->user->username])</h1>
    <p>
        Hi {{ $mentionedUser->emailName }},<br>
        <br>
        <a href="{{ route('member.show', $post->user) }}">{{ '@' . $post->user->username }}</a>
        heeft je genoemd in
        <a href="{{ route('topic.show', ['forum' => $post->topic->forum, 'topic' => $post->topic, 'slug' => $post->topic->slug, 'post' => $post->id]) }}">{{ $post->topic->title }}</a>.<br>
        <br> 
        Klik op de knop hieronder om het bericht te bekijken.
    </p>
    <p>
        <a class="btn" href="{{ route('topic.show', ['forum' => $post->topic->forum, 'topic' => $post->topic, 'slug' => $post->topic->slug, 'post' => $post->id]) }}">Bekijk bericht</a>
    </p>
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
@endsection
