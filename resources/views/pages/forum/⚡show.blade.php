<?php

use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Forum $forum;

    public function mount(Forum $forum)
    {
        $this->forum = $forum;
    }

    #[Computed]
    public function topics(): LengthAwarePaginator
    {
        return $this->forum->topics()
            ->with(['areas', 'latestPost.user'])
            ->withCount('posts')
            ->latest()
            ->paginate(Topic::PAGINATE_COUNT, pageName: 'p')
            ->setPath(route('forum.show', $this->forum));
    }

    public function render()
    {
        return $this->view()
            ->title($this->forum->name);
    }
};
?>

<div>
    <x-header :intro="$forum->description" :title="$forum->name">
        <x-slot:actions>
            <x-btn class="m:hide" :href="route('topic.create', $forum)" icon="plus" primary small>@lang('forum/show.new_topic')</x-btn>
            <x-btn class="m:show" :href="route('topic.create', $forum)" icon="plus" primary>@lang('forum/show.new_topic')</x-btn>
        </x-slot:actions>
    </x-header>
    <div class="flex flex-col flex-gap-l">
        @if ($this->topics->isNotEmpty())
            <div class="panel">
                <ul class="topicList">
                    @foreach ($this->topics as $topic)
                        @php
                            $topicUser = $topic->postUsers()->first(fn ($postUser) => $postUser['user']->id === $topic->user_id);
                        @endphp
                        <li class="topicListItem">
                            @if ($topic->latestPost->user)
                                <x-avatar
                                    img-only
                                    :title="'@' . $topic->latestPost->user->username"
                                    :user="$topic->latestPost->user"
                                />
                            @endif
                            <div class="topicListItem__text">
                                <a class="topicListItem__title" href="{{ route('topic.show', [$forum, $topic, $topic->slug]) }}" wire:navigate>{{ $topic->title }}</a>
                                <ul class="meta">
                                    @if ($topic->areas->isNotEmpty())
                                        <li class="meta__item">
                                            <div>
                                                @foreach ($topic->areas as $area)
                                                    <a href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>@if(!$loop->last), @endif
                                                @endforeach
                                            </div>
                                        </li>
                                    @endif
                                    <li class="meta__item">{{ trans_choice('forum/show.posts', $topic->posts_count, ['count' => $topic->posts_count]) }}</li>
                                    @if ($topic->latestPost)
                                        <li class="meta__item">
                                            <a href="{{ route('topic.show', [$forum, $topic, $topic->slug, 'p' => ceil($topic->posts_count / Topic::PAGINATE_COUNT)]) }}#laatste" wire:navigate>
                                                {{ $topic->latestPost?->created_at->diffForHumans() ?? '' }}
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
                    @endforeach
                </ul>
            </div>
        @else
            <div class="panel panel--padded">
                <p>@lang('forum/show.no_topics')</p>
            </div>
        @endif
        {{ $this->topics->links() }}
    </div>
</div>