<?php

use App\Events\PostLiked;
use App\Models\Post;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    public int $number;

    public string $body;

    public bool $isEditing = false;

    public bool $isLiked = false;

    public Post $post;

    public function cancelEdit()
    {
        $this->isEditing = false;
    }

    public function edit()
    {
        $this->isEditing = true;
    }

    public function mount(Post $post)
    {
        $this->post = $post;
        $this->post->loadMissing('likes', 'likes.user');

        $this->body = $post->body;
        $this->isLiked = $post->likes->contains('user_id', auth()->id());
    }

    #[Computed]
    public function postUrl(): string
    {
        return route('topic.show', [
            'forum' => $this->post->topic->forum,
            'topic' => $this->post->topic,
            'slug' => $this->post->topic->slug,
            'post' => $this->post->id,
        ]);
    }

    public function rules() {
        return [
            'body' => 'required|string',
        ];
    }

    public function submit()
    {
        $this->validate();

        $this->post->update([
            'body' => $this->body,
        ]);

        $this->isEditing = false;
    }

    public function toggleLike()
    {
        if ($this->isLiked) {
            $this->post->likes()->where('user_id', auth()->id())->delete();
        } else {
            $this->post->likes()->create(['user_id' => auth()->id()]);
            event(new PostLiked($this->post));
        }

        $this->isLiked = !$this->isLiked;
    }
};
?>

<li class="post" id="post-{{ $post->id }}">
    <header class="post__header">
        <div class="post__user">
            <x-avatar :user="$post->user" />
            <div class="post__user-name">
                <a class="post__username" href="{{ route('member.show', $post->user) }}" wire:navigate>{{ $post->user->username }}</a>
                @auth
                    <ul class="meta">
                        <li class="meta__item">{{ $post->user->name }}</li>
                        @if ($post->user->area)
                            <li class="meta__item">{{ $post->user->area->name }}</li>
                        @endif
                    </ul>
                @endauth
            </div>
        </div>
        <div class="post__actions">
            @auth
                <x-popout>
                    @if ($post->user_id !== auth()->id())
                        <x-popout.item
                            icon="send"
                            :label="__('ui.private_message')"
                            :navigate="false"
                            wire:click="$dispatch('openModal', { component: 'messages.message-modal', arguments: { username: '{{ $post->user->username }}' } })"
                        />
                        {{--
                        <x-popout.item icon="reply" :label="__('ui.reply')" />
                        <x-popout.item icon="flag" :label="__('ui.report')" />
                        --}}
                    @endif
                    @if ($post->user_id == auth('web')->id() || auth('web')->user()->is_admin)
                        <x-popout.item icon="pencil" :label="__('ui.edit')" wire:click="edit" />
                        <x-popout.item icon="trash" danger :label="__('ui.delete')" wire:click="delete" />
                    @endif
                </x-popout>
            @endauth
        </div>
    </header>
    @if ($isEditing)
        <form class="flex flex-col flex-gap-m" wire:submit="submit">
            <x-editor model="body" />
            <div class="flex flex-gap-m">
                <x-btn primary submit>Opslaan</x-btn>
                <x-btn text wire:click="cancelEdit">Annuleren</x-btn>
            </div>
        </form>
    @else
        <div class="formatted">{!! $post->body_transformed !!}</div>
    @endif
    <footer class="post__footer">
        <div @class([
            'post__like',
            'post__like--liked' => $this->isLiked,
        ])>
            @auth
                <button class="post__like-btn" wire:click="toggleLike" wire:loading.attr="disabled">
                    <x-icon icon="thumbs-up" />
                </button>
            @else
                <div class="post__like-icon"><x-icon icon="thumbs-up" /></div>
            @endauth
            <div class="post__like-count">{{ $post->likes->count() }}</div>
        </div>
        <ul class="meta">
            <li class="meta__item">
                <time class="m:hide" datetime="{{ $post->created_at->toIso8601String() }}">{{ time_diff($post->created_at) . ' ' . __('ui.ago') }}</time>
                <time class="m:show"datetime="{{ $post->created_at->toIso8601String() }}">{{ time_diff($post->created_at, long: true) . ' ' . __('ui.ago') }}</time>
            </li>
            <li class="meta__item">
                <a
                    data-share-title="{{ $post->topic->title }}"
                    href="{{ $this->postUrl }}"
                    x-data="share()"
                >#{{ $number }}</a>
            </li>
        </ul>
    </footer>
</li>