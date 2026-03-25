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

    #[Url('forums')]
    public array $selectedForumIds = [];

    #[Computed]
    public function forums(): Collection
    {
        return Forum::query()->get();
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
                        <x-topic-list-item :topic="$topic" wire:key="topic-{{ $topic->id }}" />
                    @endforeach
                </ul>
            </div>
            {{ $this->topics->links() }}
        @else
            <div class="panel panel--padded">
                <p>@lang('area/show.no_topics')</p>
            </div>
        @endif
    </div>
</div>
