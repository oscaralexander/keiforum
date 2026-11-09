<?php

use App\Constants\Event;
use App\Lib\OpenGraphImage;
use App\Models\Topic;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component
{   
    use WithPagination;

    public string $pageName = 'p';

    public Topic $topic;

    #[Computed]
    public function firstPost(): ?Post
    {
        return $this->topic->firstPost;
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
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

    /**
     * The members with the most posts in this topic.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function mostActiveMembers(): Collection
    {
        $inThisTopic = fn (Builder $query) => $query->whereBelongsTo($this->topic);

        return User::query()
            ->whereHas('posts', $inThisTopic)
            ->withCount(['posts' => $inThisTopic])
            ->orderByDesc('posts_count')
            ->orderBy('username')
            ->limit(5)
            ->get();
    }

    public function mount(Topic $topic)
    {
        if ($this->topic->activity_id) {
            throw new HttpResponseException(new RedirectResponse($this->topic->url(array_filter(['post' => request()->query('post')])), 301));
        }

        $this->topic->loadCount(['posts']);
        $this->topic->loadMissing(['areas', 'firstPost', 'forum', 'poll', 'user']);
        $this->topic->firstPost?->loadCount('likes');

        if ($page = $this->topic->pageOfPost((int) request()->query('post'))) {
            $this->setPage($page, pageName: 'p');
        }
    }

    #[On(Event::TOPIC_UPDATED)]
    public function render()
    {
        return $this->view()
            ->title($this->topic->title)
            ->layoutData([
                'canonical' => canonical_url($this->posts->currentPage()),
                'description' => $this->topic->description ?: null,
                'ogImage' => app(OpenGraphImage::class)->url($this->topic),
            ]);
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
};
?>

@push('meta')
    @if (!$topic->is_visible)
        <meta content="noindex" name="robots">
    @endif
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
    @if ($topic->is_visible)
        <x-schema :data="$this->schema" />
    @endif
    <x-header
        contrast
        :home="__('nav.forums')"
        :path="[
            ['label' => $topic->forum->name, 'href' => route('forum.show', $topic->forum)],
        ]"
        :title="$topic->title"
    >
        <x-slot:meta>
            <div class="headerMeta__item">
                <x-icon class="headerMeta__icon" icon="user" />
                <a href="{{ route('member.show', $topic->user) }}" wire:navigate>{{ $topic->user->username }}</a>
            </div>
            <div class="headerMeta__item">
                <x-icon class="headerMeta__icon" icon="pencil" />
                <time datetime="{{ $topic->created_at->toDateString() }}">{{ $topic->created_at->translatedFormat('j M Y') }}</time>
            </div>
            @if ($topic->areas->isNotEmpty())
                <x-header.area-list :areas="$topic->areas" />
            @endif
        </x-slot:meta>
    </x-header>
    <div class="wrapper">
        <div class="sidebarLayout">
            <div class="sidebarLayout__main flex flex-col flex-gap-m">
                @if ($topic->poll)
                    <livewire:poll :topic="$topic" wire:key="poll-{{ $topic->id }}" />
                @endif
                <livewire:replies :topic="$topic" wire:key="replies-{{ $topic->id }}" />
                <livewire:online-members />
            </div>
            <aside class="sidebarLayout__side">
                @if ($this->mostActiveMembers->isNotEmpty())
                    <section class="sidebarLayout__section">
                        <h2 class="sidebarLayout__title">@lang('topic/show.most_active_members')</h2>
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
                    </section>
                @endif
            </aside>
        </div>
    </div>
</div>