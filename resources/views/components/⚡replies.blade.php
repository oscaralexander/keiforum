<?php

use App\Constants\Event;
use App\Events\PostCreated;
use App\Events\PostSaved;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The posts of a topic with the reply form. On an activity the topic is
 * created on the first reply, so it can be null until then.
 */
new class extends Component
{
    use WithPagination;

    #[Locked]
    public ?Activity $activity = null;

    public string $body = '';

    public string $pageName = 'p';

    public bool $subscribe = true;

    #[Locked]
    public ?Topic $topic = null;

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        if (! $this->topic) {
            return new Paginator([], 0, Post::PAGINATE_COUNT, options: ['pageName' => 'p']);
        }

        $paginator = $this->topic->posts()
            ->with(['likes.user', 'user.area'])
            ->withCount('likes')
            ->withTrashed()
            ->paginate(Post::PAGINATE_COUNT, pageName: 'p')
            ->setPath($this->topic->url());

        foreach ($paginator->items() as $post) {
            $post->setRelation('topic', $this->topic);
        }

        return $paginator;
    }

    public function mount(): void
    {
        if (! $this->topic) {
            return;
        }

        $this->topic->loadMissing(['activity', 'firstPost', 'forum']);

        if ($page = $this->topic->pageOfPost((int) request()->query('post'))) {
            $this->setPage($page, pageName: 'p');
        }

        if (auth()->check()) {
            $pivot = $this->topic->trackedByUsers()->where('user_id', auth()->id())->first()?->pivot;
            $this->subscribe = $pivot?->is_subscribed ?? true;

            $highestPostId = $this->posts->getCollection()->max('id');

            if ($highestPostId > ($pivot?->last_read_post_id ?? 0)) {
                $this->topic->trackedByUsers()->syncWithoutDetaching([
                    auth()->id() => ['last_read_post_id' => $highestPostId],
                ]);
            }
        }
    }

    /**
     * Only the opening post of a forum topic; every post on an activity is
     * a reply.
     */
    public function isFirstPost(Post $post): bool
    {
        return ! $this->activity && $post->id === $this->topic->firstPost?->id;
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }

    public function submit(): void
    {
        Gate::authorize('create', Post::class);

        $this->validate();

        $topic = $this->topic ?? $this->activity->findOrCreateTopic();

        $post = Post::create([
            'body' => $this->body,
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
        ]);

        PostSaved::dispatch($post);

        if ($post->wasRecentlyCreated) {
            PostCreated::dispatch($post);
        }

        $topic->trackedByUsers()->syncWithoutDetaching([
            auth()->id() => ['is_subscribed' => $this->subscribe],
        ]);

        $this->redirect($topic->url(['post' => $post->id]), navigate: true);
    }

    public function updatedSubscribe(bool $value): void
    {
        Gate::authorize('create', Post::class);

        if (! $this->topic) {
            return;
        }

        $this->topic->trackedByUsers()->syncWithoutDetaching([
            auth()->id() => ['is_subscribed' => $value],
        ]);
    }

    #[On(Event::REPLY_TO_POST)]
    public function replyToPost(string $username, int $number, string $postUrl): void
    {
        $this->body = sprintf('<p><a href="%s" data-mention="true">@%s#%d</a></p>', $postUrl, $username, $number);
        $this->js("document.querySelector('.reply').scrollIntoView({ behavior: 'smooth', block: 'start' })");
    }
};
?>

<div class="flex flex-col flex-gap-m">
    @auth
        @push('scripts.auth')
            @vite(['resources/js/auth.js'])
        @endpush
    @endauth
    <div class="panel">
        {{ $slot }}
        @if ($this->posts->isNotEmpty())
            <ol>
                @foreach ($this->posts as $post)
                    @if (!$post->trashed())
                        <livewire:post
                            :is-first-post="$this->isFirstPost($post)"
                            :number="(($this->posts->currentPage() - 1) * Post::PAGINATE_COUNT) + ($loop->index + 1)"
                            :post="$post"
                            wire:key="post-{{ $post->id }}"
                        />
                    @else
                        <x-deleted-post :post="$post" />
                    @endif
                @endforeach
            </ol>
        @endif
        <section class="reply">
            @auth
                <div class="flex flex-col flex-gap-m">
                    <div class="m:hide">
                        <x-input.toggle id="subscribeMobile" :label="__('post/create.subscribe')" wire:model.live="subscribe" />
                    </div>
                    <form class="flex flex-col flex-gap-m" wire:submit="submit">
                        <x-field model="body">
                            <x-editor model="body" />
                        </x-field>
                        <div class="flex flex-align-center flex-justify-spaceBetween">
                            <x-btn primary submit>@lang('post/create.reply')</x-btn>
                            <div class="m:show">
                                <x-input.toggle id="subscribeDesktop" :label="__('post/create.subscribe')" wire:model.live="subscribe" />
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <p class="text-align-center text-color-lc">
                    @lang('topic/show.reply_login', ['login_url' => login_url()])
                </p>
            @endauth
        </section>
    </div>
    {{ $this->posts->links() }}
    @script
        <script>
            const params = new URLSearchParams(window.location.search);
            const postId = params.get('post');
            const scrollToLatest = window.location.hash === '#laatste';
            let $post;

            if (postId) {
                $post = document.getElementById(`post-${postId}`);
            }

            if (scrollToLatest) {
                $post = document.querySelector('.post:last-child');
            }

            if ($post) {
                $nextTick(() => {
                    $post.classList.add('is-highlighted');
                    $post.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                });
            }
        </script>
    @endscript
</div>
