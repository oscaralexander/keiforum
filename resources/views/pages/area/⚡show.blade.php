<?php

use App\Models\Area;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Area $area;

    /** @var array<int> */
    #[Url]
    public array $selectedForumIds = [];

    #[Computed]
    public function forums(): Collection
    {
        return Forum::query()->get();
    }

    public function mount(Area $area): void
    {
        $this->area = $area;
        $this->selectedForumIds = Forum::query()->pluck('id')->toArray();
    }

    public function updatedSelectedForumIds(): void
    {
        $this->resetPage('p');
    }

    #[Computed]
    public function topics(): LengthAwarePaginator
    {
        return Topic::query()
            ->whereHas('areas', fn ($q) => $q->where('areas.id', $this->area->id))
            ->when(
                !empty($this->selectedForumIds),
                fn ($q) => $q->whereIn('forum_id', $this->selectedForumIds),
            )
            ->with(['areas', 'forum', 'latestPost.user'])
            ->withCount('posts')
            ->latest()
            ->paginate(Topic::PAGINATE_COUNT, pageName: 'p')
            ->setPath(route('area.show', $this->area));
    }

    public function render()
    {
        return $this->view()
            ->title($this->area->name);
    }
};
?>

<div>
    <x-header :home="__('nav.forums')" :title="$area->name" />
    <div class="flex flex-col flex-gap-l">
        <ul class="flex flex-gap-s">
            @foreach ($this->forums as $forum)
                <li>
                    <label class="chip" for="forum-{{ $forum->id }}">
                        <input
                            id="forum-{{ $forum->id }}"
                            type="checkbox"
                            value="{{ $forum->id }}"
                            wire:model.live="selectedForumIds"
                        />
                        <x-icon icon="check" />
                        {{ $forum->name }}
                    </label>
                </li>
            @endforeach
        </ul>
        @if ($this->topics->isNotEmpty())
            <div class="panel">
                <ul class="topicList">
                    @foreach ($this->topics as $topic)
                        @php
                            $topicUser = $topic->postUsers()->first(fn ($postUser) => $postUser['user']->id === $topic->user_id);
                        @endphp
                        <li class="topicListItem">
                            @if ($topic->latestPost?->user)
                                <x-avatar
                                    img-only
                                    :title="'@' . $topic->latestPost->user->username"
                                    :user="$topic->latestPost->user"
                                />
                            @endif
                            <div class="topicListItem__text">
                                <a class="topicListItem__title" href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug]) }}" wire:navigate>{{ $topic->title }}</a>
                                <ul class="meta">
                                    <li class="meta__item">
                                        <a href="{{ route('forum.show', $topic->forum) }}" wire:navigate>{{ $topic->forum->name }}</a>
                                    </li>
                                    <li class="meta__item">{{ trans_choice('forum/show.posts', $topic->posts_count, ['count' => $topic->posts_count]) }}</li>
                                    @if ($topic->latestPost)
                                        <li class="meta__item">
                                            <a href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug, 'p' => ceil($topic->posts_count / Topic::PAGINATE_COUNT)]) }}#laatste" wire:navigate>
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
                <p>@lang('area/show.no_topics')</p>
            </div>
        @endif
        {{ $this->topics->links() }}
    </div>
</div>
