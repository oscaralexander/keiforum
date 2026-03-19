<?php

use App\Constants\Event;
use App\Events\PostSaving;
use App\Models\Area;
use App\Models\Forum;
use App\Models\Topic;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component
{   
    use WithPagination;

    public string $pageName = 'p';

    public Topic $topic;

    public string $body;

    #[Computed]
    public function firstPost(): ?Post
    {
        return $this->topic->firstPost;
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        $paginator = $this->topic->posts()
            ->withTrashed()
            ->with(['likes', 'user.area'])
            ->withCount('likes')
            ->paginate(Post::PAGINATE_COUNT, pageName: 'p')
            ->setPath(route('topic.show', [$this->topic->forum, $this->topic, $this->topic->slug]));

        foreach ($paginator->items() as $post) {
            $post->setRelation('topic', $this->topic);
        }

        return $paginator;
    }

    public function mount(Topic $topic)
    {
        $this->topic->loadCount(['posts']);
        $this->topic->loadMissing(['areas', 'firstPost', 'forum']);

        if ($postId = request()->query('post')) {
            $this->paginateToPost((int) $postId);
        }
    }

    protected function paginateToPost(int $postId): void
    {
        $post = $this->topic->posts()->find($postId);
        
        if (!$post) {
            return;
        }

        $postPosition = $this->topic->posts()
            ->where('id', '<=', $postId)
            ->count();

        if ($postPosition > 0) {
            $page = (int) ceil($postPosition / Post::PAGINATE_COUNT);
            $this->setPage($page, pageName: 'p');
        }
    }

    #[On(Event::TOPIC_UPDATED)]
    public function render()
    {
        return $this->view()
            ->title($this->topic->title);
    }

    public function rules()
    {
        return [
            'body' => 'required|string',
        ];
    }

    #[Computed]
    public function schema(): array
    {
        $skip = $this->posts->currentPage() === 1 ? 1 : 0;

        return [
            '@context' => 'https://schema.org',
            '@id' => route('topic.show', [$this->topic->forum, $this->topic, $this->topic->slug]),
            '@type' => 'DiscussionForumPosting',
            'articleBody' => $this->firstPost?->bodyPlainText,
            'author' => [
                '@type' => 'Person',
                'name' => $this->topic->user->username,
                'url' => route('member.show', $this->topic->user),
            ],
            'comment' => $this->posts->skip($skip)->map(function (Post $post) {
                return [
                    '@type' => 'Comment',
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'datePublished' => $post->created_at->toIso8601String(),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $post->user->username,
                        'url' => route('member.show', $post->user),
                    ],
                    'interactionStatistic' => [
                        [
                            '@type' => 'InteractionCounter',
                            'interactionType' => [
                                '@type' => 'LikeAction',
                            ],
                            'userInteractionCount' => $post->likes_count,
                        ],
                    ],
                    'text' => $post->bodyPlainText,
                ];
            })->values()->all(),
            'commentCount' => ($this->topic->posts_count - 1),
            'datePublished' => $this->topic->created_at->toIso8601String(),
            'dateModified' => $this->topic->updated_at->toIso8601String(),
            'headline' => $this->topic->title,
            'interactionStatistic' => [
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => [
                        '@type' => 'CommentAction',
                    ],
                    'userInteractionCount' => ($this->topic->posts_count - 1),
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => [
                        '@type' => 'LikeAction',
                    ],
                    'userInteractionCount' => $this->firstPost?->likes_count,
                ],
            ],
            'mainEntityOfPage' => route('topic.show', [$this->topic->forum, $this->topic, $this->topic->slug]),
            'url' => route('topic.show', [$this->topic->forum, $this->topic, $this->topic->slug]),
        ];
    }

    public function submit()
    {
        Gate::authorize('create', Post::class);

        $this->validate();

        $post = Post::create([
            'body' => $this->body,
            'topic_id' => $this->topic->id,
            'user_id' => auth()->id(),
        ]);

        PostSaving::dispatch($post);

        $this->redirect(route('topic.show', [$this->topic->forum, $this->topic, 'bericht' => $post->id]), navigate: true);
    }
};
?>

@push('meta')
    <meta property="og:description" content="{{ $this->firstPost?->bodyPlainText }}">
    @if ($this->posts->hasPages())
        @if (!$this->posts->onFirstPage())
            <link href="{{ $this->posts->previousPageUrl() }}" rel="prev">
        @endif
        @if (!$this->posts->onLastPage())
            <link href="{{ $this->posts->nextPageUrl() }}" rel="next">
        @endif
    @endif
@endpush

<div>
    <x-schema :data="$this->schema" />
    <x-header
        :areas="$topic->areas"
        :home="__('nav.forums')"
        :path="[
            ['label' => $topic->forum->name, 'href' => route('forum.show', $topic->forum)],
        ]"
        :title="$topic->title"
    />
    <div class="flex flex-col flex-gap-l">
        <div class="panel">
            <ol>
                @foreach ($this->posts as $post)
                    @if (!$post->trashed())
                        <livewire:post
                            :is-first-post="$post->id === $topic->firstPost?->id"
                            :number="(($this->posts->currentPage() - 1) * Post::PAGINATE_COUNT) + ($loop->index + 1)"
                            :post="$post"
                            wire:key="post-{{ $post->id }}"
                        />
                    @else
                        <x-deleted-post :post="$post" />
                    @endif
                @endforeach
            </ol>
            <section class="reply">
                @auth
                    <div class="flex flex-col flex-gap-m">
                        <h3>@lang('topic/show.reply')</h3>
                        <form class="flex flex-col flex-gap-m" wire:submit="submit">
                            <x-field model="body">
                                <x-editor model="body" />
                            </x-field>
                            <div class="flex">
                                <x-btn primary submit>@lang('post/create.reply')</x-btn>
                            </div>
                        </form>
                    </div>
                @else
                    <p class="text-align-center text-color-lc">
                        🔒 @lang('topic/show.reply_login')
                    </p>
                @endauth
            </section>
        </div>
        {{ $this->posts->links() }}
    </div>
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