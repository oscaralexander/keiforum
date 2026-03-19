@extends('mail.layouts.default')

@section('title', __('mail/post/like_threshold_reached.subject', ['count' => $count]))

@section('content')
    <h1>@lang('mail/post/like_threshold_reached.subject', ['count' => $count])</h1>
    <p>
        Hi {{ $user->emailName }},<br>
        <br>
        Je bericht in
        <a href="{{ route('topic.show', ['forum' => $post->topic->forum, 'topic' => $post->topic, 'slug' => $post->topic->slug, 'post' => $post->id]) }}">{{ $post->topic->title }}</a>
        kreeg {{ $count }} likes!
    </p>
    <p>
        <a class="btn" href="{{ route('topic.show', ['forum' => $post->topic->forum, 'topic' => $post->topic, 'slug' => $post->topic->slug, 'post' => $post->id]) }}">Bekijk mijn bericht</a>
    </p>
@endsections