<?php

use App\Models\Forum;
use App\Models\Topic;
use App\Enums\AdType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url('ad_types')]
    public array $adTypes = [];

    public Forum $forum;

    public function mount(Forum $forum)
    {
        $this->forum = $forum;
    }

    #[Computed]
    public function topics(): LengthAwarePaginator
    {
        return $this->forum->topics()
            ->with(['areas', 'latestPost.user', 'poll'])
            ->when(
                $this->forum->is_marketplace && !empty($this->adTypes),
                fn ($q) => $q->where(function ($query) {
                    $query->whereIn('ad_type', $this->adTypes)
                          ->orWhereNull('ad_type');
                })
            )
            ->withCount('pollVotes', 'posts')
            ->orderByDesc('is_pinned')
            ->orderByDesc(
                DB::raw('(SELECT MAX(p.id) FROM posts p WHERE p.topic_id = topics.id)')
            )
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
    <x-header :home="__('nav.forums')" :intro="$forum->description" :title="$forum->name">
        <x-slot:actions>
            <x-btn class="m:hide" :href="route('topic.create', $forum)" icon="plus" primary small>{{ __('forum/show.new_' . ($forum->is_marketplace ? 'ad' : 'topic')) }}</x-btn>
            <x-btn class="m:show" :href="route('topic.create', $forum)" icon="plus" primary>{{ __('forum/show.new_' . ($forum->is_marketplace ? 'ad' : 'topic')) }}</x-btn>
        </x-slot:actions>
    </x-header>
    <div class="flex flex-col flex-gap-l">
        @if ($forum->is_marketplace)
            <ul class="flex flex-gap-s">
                @foreach (AdType::options() as $value => $label)
                    <li>
                        <label class="chip" for="ad-type-{{ $value }}">
                            <input
                                id="ad-type-{{ $value }}"
                                type="checkbox"
                                value="{{ $value }}"
                                wire:model.live="adTypes"
                            />
                            <x-icon icon="check" />
                            {{ $label }}
                        </label>
                    </li>
                @endforeach
            </ul>
        @endif
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
                <p>@lang('forum/show.no_topics')</p>
            </div>
        @endif
    </div>
</div>