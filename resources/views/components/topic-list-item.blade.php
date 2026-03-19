@use('App\Models\Topic')

@blaze

@props([
    'topic' => null,
])

<li {{ $attributes->class(['topicListItem']) }}>
    @if ($topic->latestPost->user)
        <x-avatar
            img-only
            :title="'@' . $topic->latestPost->user->username"
            :user="$topic->latestPost->user"
        />
    @endif
    <div class="topicListItem__text">
        <div>
            @if ($topic->is_locked)
                <x-icon class="topicListItem__icon" icon="lock" />
            @endif
            @if ($topic->is_pinned)
                <x-icon class="topicListItem__icon" icon="pin" />
            @endif
            <a class="topicListItem__title" href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug]) }}" wire:navigate>{{ $topic->title }}</a>
            @if ($topic->ad_type) 
                <span class="topicListItem__adType topicListItem__adType--{{ $topic->ad_type->value }}">{{ $topic->ad_type->label() }}</span>
            @endif
        </div>
        <ul class="meta">
            <li class="meta__item">
                <x-icon icon="message-circle" />
                {{  $topic->posts_count }}
            </li>
            @if ($topic->areas->isNotEmpty())
                <li class="meta__item">
                    <div>
                        @foreach ($topic->areas as $area)
                            <a href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </li>
            @endif
            @if ($topic->latestPost)
                <li class="meta__item">
                    <a href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug, 'post' => $topic->latestPost->id]) }}" wire:navigate>
                        {{ time_diff($topic->latestPost?->created_at) }} @lang('ui.ago')
                        <x-icon icon="arrow-right" />
                    </a>
                </li>
            @endif
        </ul>
    </div>
    @if ($topic->postUsers()->count() > 1)
        <div class="avatarList">
            @foreach ($topic->postUsers()->take(5) as $postUser)
                @if ($postUser['user']->id !== $topic->user_id)
                    <x-avatar
                        img-only
                        :title="'@' . $postUser['user']->username . ' · ' . trans_choice('forum/show.posts', $postUser['post_count'], ['count' => $postUser['post_count']])"
                        :user="$postUser['user']"
                    />
                @endif
            @endforeach
            @if ($topic->postUsers()->count() > 5)
                <div class="avatarList__more">+{{ $topic->postUsers()->count() - 5 }}</div>
            @endif
        </div>
    @endif
</li>