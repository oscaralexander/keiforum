<?php

use App\Enums\Gender;
use App\Enums\AvatarSize;
use App\Models\User;
use App\Models\Topic;
use App\Models\Post;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Collection;

new class extends Component
{
    public User $user;
    
    public function mount(User $user)
    {
        $this->user = $user;
        $this->user->load('area')->loadCount('posts');
    }

    public function render()
    {
        return $this->view()
            ->title($this->user->username);
    }


    #[Computed]
    public function topics(): Collection
    {
        $userPostStats = Post::query()
            ->selectRaw('topic_id, COUNT(id) as posts_count, MAX(id) as latest_post_id, MAX(created_at) as latest_post_created_at')
            ->where('user_id', $this->user->id)
            ->groupBy('topic_id');

        return Topic::query()
            ->with('forum')
            ->joinSub($userPostStats, 'user_posts', 'topics.id', '=', 'user_posts.topic_id')
            ->select('topics.*', 'user_posts.posts_count', 'user_posts.latest_post_id', 'user_posts.latest_post_created_at')
            ->orderByDesc('user_posts.posts_count')
            ->get();
    }
};
?>

<div>
    <x-header
        :path="[['label' => __('members/index.title'), 'href' => route('members')]]"
        :title="$user->username"
    />
    <div class="panel panel--padded">
        <div class="profile">
            <div class="profile__avatar">
                <x-avatar img-only :size="AvatarSize::L" :user="$user" />
            </div>
            <div class="profile__main">
                <section class="profile__section">
                    @auth
                        <header class="profile__header">
                            <x-avatar class="profile__header-avatar" img-only :size="AvatarSize::L" :user="$user" />
                            <div class="profile__header-content">
                                <div class="profile__header-details">
                                    <h2 class="profile__header-name">{{ $user->name }}</h2>
                                    <ul class="meta meta--large">
                                        @if ($user->area)
                                            <li class="meta__item">{{  $user->area->name }}</li>
                                        @endif
                                        @if (!empty($user->gender))
                                            <li class="meta__item">{{ $user->gender->label() }}</li>
                                        @endif
                                        @if (!empty($user->birthdate))
                                            <li class="meta__item">{{ $user->age }}</li>
                                        @endif
                                    </ul>
                                </div>
                                <div class="profile__header-actions">
                                    @if ($user->id === auth()->id())
                                        <x-btn class="m:hide" :href="route('profile')" icon="pencil" primary small>@lang('ui.edit')</x-btn>
                                        <x-btn class="m:show" :href="route('profile')" icon="pencil" primary>@lang('ui.edit')</x-btn>
                                    @else
                                        <x-btn class="m:hide" icon="send" primary small wire:click="$dispatch('openModal', { component: 'messages.message-modal', arguments: { username: '{{ $user->username }}' } })">@lang('ui.private_message')</x-btn>
                                        <x-btn class="m:show" icon="send" primary wire:click="$dispatch('openModal', { component: 'messages.message-modal', arguments: { username: '{{ $user->username }}' } })">@lang('ui.private_message')</x-btn>
                                    @endif
                                </div>
                            </div>
                        </header>
                        @if (!empty($user->bio))
                            <div class="formatted profile__bio">
                                <p>{{ nl2br($user->bio) }}</p>
                            </div>
                        @endif
                    @else
                        <div>🔒 Details alleen zichtbaar voor leden.</div>
                    @endauth
                </section>
                <section class="profile__section">
                    <div class="profile__stats flex flex-gap-l">
                        <div class="flex flex-col flex-flex flex-gap-xs">
                            <h4>@lang('members/show.member_since')</h4>
                            <p>{{ $user->created_at->translatedFormat('j F Y') }}</p>
                        </div>
                        <div class="flex flex-col flex-flex flex-gap-xs">
                            <h4>@lang('members/show.posts_count')</h4>
                            <p>{{ $user->posts_count }}</p>
                        </div>
                        <div class="flex flex-col flex-flex flex-gap-xs">
                            <h4>@lang('members/show.last_seen')</h4>
                            <p>{{ time_diff($user->last_seen_at, long: true) }} @lang('ui.ago')</p>
                        </div>
                    </div>
                </section>
                <section class="profile__section">
                    <h3 class="profile__section-title">@lang('members/show.topics')</h3>
                    <div class="profile__topics">
                        @forelse ($this->topics as $topic)
                            <div class="profile__topic">
                                <a href="{{ route('topic.show', [$topic->forum, $topic, $topic->slug, 'post' => $topic->latest_post_id]) }}" wire:navigate>{{ $topic->title }}</a>
                                <ul class="meta">
                                    <li class="meta__item">{{ trans_choice('members/show.topic_posts_count', $topic->posts_count, ['count' => $topic->posts_count]) }}</li>
                                    <li class="meta__item">{{ time_diff(Carbon\Carbon::parse($topic->latest_post_created_at)) }} @lang('ui.ago')</li>
                                </ul>
                            </div>
                        @empty
                            <div class="profile__topic text-color-lc">@lang('members/show.no_topics')</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>