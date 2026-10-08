@extends('mail.layouts.default')

@section('title', __('mail/digest.subject'))

@section('content')
    <h1>Dit gebeurde er deze week op Keiforum</h1>
    <p>
        Hi {{ $user->emailName }},<br>
        <br>
        Benieuwd waar Amersfoort het deze week over had? Hier is een overzicht.
    </p>
    @if ($digest['popular_topics'])
        <h2>Populair deze week</h2>
        <table class="topics" role="presentation">
            @foreach ($digest['popular_topics'] as $topic)
                <tr>
                    <td class="topics__avatar" style="padding-right: 12px; width: 32px;">
                        <img alt="{{ $topic['username'] }}" height="32" src="{{ $topic['avatar_url'] }}" style="border-radius: 50%; display: block;" width="32">
                    </td>
                    <td>
                        <a href="{{ $topic['url'] }}" style="color: #c93020; font-weight: 600;">{{ $topic['title'] }}</a><br>
                        <small>{{ $topic['forum'] }} · {{ trans_choice('mail/digest.posts_count', $topic['posts_count'], ['count' => $topic['posts_count']]) }}</small>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
    @if ($digest['new_topics'])
        <h2>Nieuwe onderwerpen</h2>
        <table class="topics" role="presentation">
            @foreach ($digest['new_topics'] as $topic)
                <tr>
                    <td class="topics__avatar" style="padding-right: 12px; width: 32px;">
                        <img alt="{{ $topic['username'] }}" height="32" src="{{ $topic['avatar_url'] }}" style="border-radius: 50%; display: block;" width="32">
                    </td>
                    <td>
                        <a href="{{ $topic['url'] }}" style="color: #c93020; font-weight: 600;">{{ $topic['title'] }}</a><br>
                        <small>{{ $topic['forum'] }}</small>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
    @if ($digest['new_members_count'] > 0)
        <p>{{ trans_choice('mail/digest.new_members', $digest['new_members_count'], ['count' => $digest['new_members_count']]) }}</p>
    @endif
    <p>
        <a class="btn" href="{{ route('home') }}">Naar Keiforum</a>
    </p>
    <p>
        Tot snel!<br>
    </p>
    <a href="{{ config('app.url') }}" target="_blank"><img alt="Keiforum" height="32" src="{{ asset('assets/img/keiforum-mail.png') }}" /></a>
    <p class="footer">
        Je ontvangt deze mail omdat je lid bent van Keiforum.
        <a href="{{ $unsubscribeUrl }}" style="color: #c93020;">Afmelden voor de wekelijkse update</a>
    </p>
@endsection
