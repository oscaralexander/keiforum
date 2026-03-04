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
        <ul class="forumList">
            @foreach ($this->forums as $forum)
                <li class="forumListItem">
                    <div class="forumListItem__icon">
                        <x-icon icon="{{ $forum->icon }}" />
                    </div>
                    <div class="forumListItem__text">
                        <div class="forumListItem__nameCount">
                            <a class="forumListItem__name" href="{{ route('forum.show', $forum->slug) }}" wire:navigate>{{ $forum->name }}</a>
                            <div class="forumListItem__count">{{ trans_choice('forum/index.topics', $forum->topics_count, ['count' => $forum->topics_count]) }}</div>
                        </div>
                        <div class="forumListItem__description">{{ $forum->description }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>