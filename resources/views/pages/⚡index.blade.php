<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Forum;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    #[Computed]
    public function forums(): Collection
    {
        return Forum::query()
            ->withCount('topics')
            ->get();
    }

    public function render()
    {
        return $this->view()
            ->title('Hallo Amersfoort!');
    }
};

?>

<div>
    <x-header hide-path :title="__('home.title')" />
    <div class="panel">
        <ul class="topicList">
            @foreach ($this->forums as $forum)
                <li class="topicListItem">
                    <div class="topicListItem__text">
                        <a class="topicListItem__title" href="{{ route('forum.show', $forum->slug) }}" wire:navigate>{{ $forum->name }}</a>
                        <div class="topicListItem__description">{{ $forum->description }}</div>
                    </div>
                    <div class="topicListItem__count">{{ trans_choice('forum/index.topics', $forum->topics_count, ['count' => $forum->topics_count]) }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>