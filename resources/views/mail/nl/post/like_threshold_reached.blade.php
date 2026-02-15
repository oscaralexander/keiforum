@extends('mail.layouts.default')

@section('title', '🎉 Je bericht kreeg ' . $count . ' likes!')

@section('content')
    <h1>🎉 Je bericht kreeg {{ $count }} likes!</h1>
    <p>
        Hallo {{ $post->user->name }},
    </p>
    <p>
        Je bericht in
        <b><a href="{{ route('topic.show', ['forum' => $post->topic->forum, 'topic' => $post->topic, 'slug' => $post->topic->slug, 'post' => $post->id]) }}">{{ $post->topic->title }}</a></b>
        kreeg {{ $count }} likes!
    </p>
    <p>
        Klik op de knop hieronder om je bericht te bekijken.
    </p>
    <p>
        <a class="button" href="{{ route('post.show', $post->id) }}">Bekijk mijn bericht</a>
    </p>
@endsection