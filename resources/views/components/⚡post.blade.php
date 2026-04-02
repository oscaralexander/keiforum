<?php

use App\Constants\Event;
use App\Events\PostLiked;
use App\Events\PostSaved;
use App\Livewire\Forms\PollForm;
use App\Models\Area;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

new class extends Component
{
    public int $number;

    public string $body;

    #[Locked]
    public bool $isEditing = false;

    #[Locked]
    public bool $isFirstPost = false;

    #[Locked]
    public bool $isLiked = false;

    public Post $post;

    public PollForm $poll;

    public string $topic_title;

    public array $topicAreas = [];

    #[Computed]
    public function areas()
    {
        return Area::all();
    }

    #[Computed]
    public function reports(): Collection
    {
        if (!auth()->user()?->is_admin) {
            return collect();
        }

        return $this->post->reports()->with('user')->get();
    }

    public function cancelEdit()
    {
        $this->isEditing = false;
    }

    public function delete()
    {
        Gate::authorize('delete', $this->post);

        $this->post->update([
            'deleted_at' => now(),
            'deleted_by_id' => auth()->id(),
        ]);

        if ($this->isFirstPost && !$this->post->topic->has_replies) {
            $this->post->topic->delete();

            return redirect()->route('forum.show', ['forum' => $this->post->topic->forum]);
        }
    }

    public function deleteReports(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->post->reports()->delete();
    }

    public function addPollOption(): void
    {
        $this->poll->addOption();
        $this->dispatch('poll-option-added');
    }

    public function removePollOption(string $id): void
    {
        $this->poll->removeOption($id);
    }

    public function reorderPollOptions(string $id, int $position): void
    {
        $this->poll->reorderOptions($id, $position);
    }

    public function edit()
    {
        Gate::authorize('update', $this->post);

        if ($this->isFirstPost) {
            $this->topic_title = $this->post->topic->title;
            $this->topicAreas = $this->post->topic->areas->pluck('id')->toArray();

            $topicPoll = $this->post->topic->poll;

            if ($topicPoll) {
                $this->poll->loadFromPoll($topicPoll, $this->post->topic->user_id);
            }
        }

        $this->isEditing = true;
    }

    public function mount()
    {
        $this->post->loadMissing('likes');
        $this->body = $this->post->body;
        $this->isLiked = $this->post->likes->contains('user_id', auth()->id());
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

    public function rules(): array
    {
        $rules = [
            'body' => ['required', 'string'],
        ];

        if ($this->isFirstPost) {
            $rules['topic_title'] = ['required', 'max:255'];
            $rules['topicAreas'] = ['nullable', 'array'];
            $rules['topicAreas.*'] = ['exists:areas,id'];
        }

        return $rules;
    }

    public function submit()
    {
        Gate::authorize('update', $this->post);

        $this->validate();

        $oldBody = $this->post->body;

        $this->post->update([
            'body' => $this->body,
        ]);

        PostSaved::dispatch($this->post, $oldBody);

        if ($this->isFirstPost) {
            $this->post->topic->update(['title' => $this->topic_title]);
            $this->post->topic->areas()->sync($this->topicAreas);

            $topicPoll = $this->post->topic->poll;

            if ($topicPoll && $this->poll->active) {
                $this->poll->saveExisting($topicPoll);
            }
        }

        $this->isEditing = false;

        if ($this->isFirstPost) {
            $this->dispatch(Event::TOPIC_UPDATED);
        }
    }

    public function reply(): void
    {
        $this->dispatch(Event::REPLY_TO_POST,
            number: $this->number,
            postUrl: $this->postUrl,
            username: $this->post->user->username,
        );
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
            <div class="post__usernameMeta">
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
                            icon="reply"
                            :label="__('ui.reply')"
                            :href="$this->postUrl"
                            wire:click="reply"
                        />
                        <x-popout.item
                            icon="send"
                            :label="__('ui.private_message')"
                            :navigate="false"
                            wire:click="$dispatch('openModal', { component: 'messages.message-modal', arguments: { username: '{{ $post->user->username }}' } })"
                        />
                        <x-popout.item
                            icon="flag"
                            :label="__('ui.report')"
                            :navigate="false"
                            wire:click="$dispatch('openModal', { component: 'posts.report-modal', arguments: { postId: {{ $post->id }} } })"
                        />
                    @endif
                    @if ($post->user_id == auth()->id() || auth()->user()->is_admin)
                        <x-popout.item icon="pencil" :label="__('ui.edit')" wire:click="edit" />
                        <x-popout.item
                            icon="trash"
                            danger
                            :label="__('ui.delete')"
                            wire:click="delete"
                            wire:confirm="{{ __('post/show.confirm_delete' . ($isFirstPost ? '_topic' : '')) }}"
                        />
                    @endif
                </x-popout>
            @endauth
        </div>
    </header>
    @if ($isEditing)
        <form class="flex flex-col flex-gap-m" wire:submit="submit">
            @if ($isFirstPost)
                <x-field>
                    <x-input.text large model="topic_title" required />
                </x-field>
                <x-field
                    class="flex-flex"
                    :label="__('topic/form.topic_areas.label')"
                    model="topicAreas"
                >
                    <x-input.multi-select
                        model="topicAreas"
                        :options="$this->areas"
                        :placeholder="__('topic/form.topic_areas.placeholder')"
                    />
                </x-field>
            @endif
            <x-editor model="body" />
            @if ($isFirstPost &&$this->poll->active)
                <x-poll-editor :form="$this->poll" />
            @endif
            <x-actions class="flex-justify-spaceBetween">
                <x-actions>
                    <x-btn primary submit>Opslaan</x-btn>
                    <x-btn text wire:click="cancelEdit">Annuleren</x-btn>
                </x-actions>
            </x-actions>
        </form>
    @else
        @if ($this->reports->isNotEmpty())
            <div class="post__report">
                <x-icon icon="flag" />
                <div class="post__report-content">
                    <div class="post__report-info">
                        <span class="post__report-labels">{{ $this->reports->pluck('type')->map->label()->implode(', ') }}</span>
                        <span class="post__report-usernames">volgens {{ $this->reports->pluck('user.username')->implode(', ') }}</span>
                    </div>
                    <x-btn icon="trash" small wire:click="deleteReports" />
                </div>
            </div>
        @endif
        <div class="formatted">{!! $post->body_transformed !!}</div>
    @endif
    <footer class="post__footer">
        <div @class([
            'post__like',
            'post__like--liked' => $this->isLiked,
        ])>
            @auth
                <button
                    aria-label="{{ __('post/show.like') }}"
                    class="post__like-btn"
                    wire:click="toggleLike"
                    wire:loading.attr="disabled"
                ><x-icon icon="thumbs-up" /></button>
            @else
                <div class="post__like-icon"><x-icon icon="thumbs-up" /></div>
            @endauth
            <div class="post__like-count">{{ $post->likes->count() }}</div>
        </div>
        <ul class="meta">
            <li class="meta__item">
                <time class="m:hide" datetime="{{ $post->created_at->toIso8601String() }}" title="{{ $post->created_at->translatedFormat('j F Y, H:i') }}">{{ time_diff($post->created_at) . ' ' . __('ui.ago') }}</time>
                <time class="m:show" datetime="{{ $post->created_at->toIso8601String() }}" title="{{ $post->created_at->translatedFormat('j F Y, H:i') }}">{{ time_diff($post->created_at, long: true) . ' ' . __('ui.ago') }}</time>
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
