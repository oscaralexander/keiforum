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
        abort_if($forum->is_hidden, 404);

        $this->forum = $forum;
    }

    #[Computed]
    public function topics(): LengthAwarePaginator
    {
        return $this->forum->topics()
            ->visible()
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
    <x-header
        hide-path
        :home="__('nav.forums')"
        :intro="$forum->description"
        :title="$forum->name"
    >
        <x-slot:cta>
            @can('createTopic', $forum)
                <x-btn :href="route('topic.create', $forum)" icon="plus" large primary :rel="auth()->guest() ? 'nofollow' : null">{{ __('forum/show.new_' . ($forum->is_marketplace ? 'ad' : 'topic')) }}</x-btn>
            @endcan
        </x-slot:cta>
    </x-header>
    <div class="wrapper">
        <div class="sidebarLayout">
            <div class="sidebarLayout__main flex flex-col flex-gap-l">
                <div class="panel">
                    @if ($forum->is_marketplace)
                        <div class="panel__body">
                            <ul class="flex flex-gap-s flex-wrap">
                                @foreach (AdType::options() as $value => $label)
                                    <li wire:key="ad-type-filter-{{ $value }}">
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
                        </div>
                    @endif
                    @if ($this->topics->isNotEmpty())
                        <ul class="topicList">
                            @foreach ($this->topics as $topic)
                                <x-topic-list-item :topic="$topic" wire:key="topic-{{ $topic->id }}" />
                            @endforeach
                        </ul>
                    @else
                        <div class="panel__body">
                            <p>@lang('forum/show.no_topics')</p>
                        </div>
                    @endif
                    @if ($this->topics->hasPages())
                        <div class="panel__body">
                            {{ $this->topics->links() }}
                        </div>
                    @endif
                </div>
            </div>
            <livewire:community-sidebar />
        </div>
        <livewire:online-members />
    </div>
</div>