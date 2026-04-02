@use('App\Enums\AvatarSize')
@use('App\Models\Topic')

@blaze

@props([
    'forum' => null,
])

<li class="forumListItem">
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
        <ul class="forumListItem__recentTopics">
            @foreach ($forum->recentTopics as $topic)
                <li class="forumListItem__recentTopic" wire:key="recent-topic-{{ $topic->id }}">
                    <div class="forumListItem__recentTopic-avatar">
                        <x-avatar :size="AvatarSize::S" :user="$topic->latestPost->user" />
                    </div>
                    <div class="forumListItem__recentTopic-titleMeta">
                        <div>
                            @if ($topic->poll)
                                <x-icon class="forumListItem__recentTopic-icon" icon="vote" />
                            @endif
                            <a class="forumListItem__recentTopic-title" href="{{ route('topic.show', [$forum, $topic, $topic->slug]) }}" wire:navigate>{{ $topic->title }}</a>
                        </div>
                        <ul class="meta">
                            <li class="meta__item">
                                <x-icon icon="message-circle" />
                                {{ $topic->posts_count }}
                            </li>
                            <li class="meta__item">
                                <a
                                    aria-label="@lang('forum/show.latest_post_label', ['topic' => $topic->title])"
                                    href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug, 'post' => $topic->latestPost->id]) }}"
                                    wire:navigate
                                >
                                    {{ time_diff($topic->latestPost?->created_at) }} @lang('ui.ago')
                                    <x-icon icon="arrow-right" />
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
        @php
            $moreTopicsCount = $forum->topics_count - 5;
        @endphp
        @if ($moreTopicsCount > 0)
            <a class="forumListItem__moreTopics" href="{{ route('forum.show', $forum->slug) }}" wire:navigate>
                {{ trans_choice('forum/index.more_topics', $moreTopicsCount, ['count' => $moreTopicsCount]) }}
            </a>
        @endif
    @endif
</li>