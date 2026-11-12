<?php

use App\Models\Area;
use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
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

    #[Url('forums')]
    public array $selectedForumIds = [];

    #[Computed]
    public function forums(): Collection
    {
        return Forum::query()->listed()->orderBy('position')->get();
    }

    /**
     * The members from this area with the most posts, leaving out those
     * who haven't posted anything yet.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function mostActiveMembers(): Collection
    {
        return User::query()
            ->active()
            ->members()
            ->whereBelongsTo($this->area)
            ->whereHas('posts')
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('username')
            ->limit(5)
            ->get();
    }

    public function mount(Area $area): void
    {
        $this->area = $area;
    }

    public function updatedSelectedForumIds(): void
    {
        $this->resetPage('p');
    }

    #[Computed]
    public function topics(): LengthAwarePaginator
    {
        return Topic::query()
            ->visible()
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
    <div class="wrapper">
        <div class="sidebarLayout">
            <div class="sidebarLayout__main">
                <div class="panel">
                    <div class="panel__body">
                        <ul class="flex flex-gap-s flex-wrap">
                            @foreach ($this->forums as $forum)
                                <li wire:key="forum-filter-{{ $forum->id }}">
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
                    </div>
                    @if ($this->topics->isNotEmpty())
                        <ul class="topicList">
                            @foreach ($this->topics as $topic)
                                <x-topic-list-item :topic="$topic" wire:key="topic-{{ $topic->id }}" />
                            @endforeach
                        </ul>
                    @else
                        <div class="panel__body">
                            <p>@lang('area/show.no_topics')</p>
                        </div>
                    @endif
                    @if ($this->topics->hasPages())
                        <div class="panel__body">
                            {{ $this->topics->links() }}
                        </div>
                    @endif
                </div>
            </div>
            <aside class="sidebarLayout__side">
                <section class="sidebarLayout__section">
                    <h2 class="sidebarLayout__title">@lang('area/show.most_active_members', ['area' => $area->name])</h2>
                    @if ($this->mostActiveMembers->isNotEmpty())
                        <ul class="sidebarLayout__list">
                            @foreach ($this->mostActiveMembers as $member)
                                <li class="member" wire:key="most-active-member-{{ $member->id }}">
                                    <x-avatar :user="$member" />
                                    <div class="member__content">
                                        <a class="member__name" href="{{ route('member.show', $member) }}" wire:navigate>{{ $member->username }}</a>
                                        <ul class="meta">
                                            <li class="meta__item">{{ trans_choice('members/index.top_posters.posts_count', $member->posts_count, ['count' => $member->posts_count]) }}</li>
                                        </ul>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="sidebarLayout__text">@lang('area/show.no_members', ['area' => $area->name])</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</div>
