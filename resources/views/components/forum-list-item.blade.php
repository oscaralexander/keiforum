@blaze

@props([
    'forum' => null,
])

@php
    $moreTopicsCount = $forum->topics_count - $forum->recentTopics->count();
@endphp

<section {{ $attributes->class(['panel', 'forumListItem']) }}>
    <header class="forumListItem__header">
        <div class="forumListItem__icon">
            <x-icon icon="{{ $forum->icon }}" />
        </div>
        <div class="forumListItem__nameDescription">
            <h2 class="forumListItem__name"><a href="{{ route('forum.show', $forum->slug) }}" wire:navigate>{{ $forum->name }}</a></h2>
            <div class="forumListItem__description">{{ $forum->description }}</div>
        </div>
    </header>
    @if ($forum->recentTopics->isNotEmpty())
        <ul class="topicList">
            @foreach ($forum->recentTopics as $topic)
                <x-topic-list-item :topic="$topic" wire:key="recent-topic-{{ $topic->id }}" />
            @endforeach
        </ul>
        @if ($moreTopicsCount > 0)
            <a class="forumListItem__moreTopics" href="{{ route('forum.show', $forum->slug) }}" wire:navigate>
                {{ trans_choice('forum/index.more_topics', $moreTopicsCount, ['count' => $moreTopicsCount]) }}
            </a>
        @endif
    @endif
</section>
