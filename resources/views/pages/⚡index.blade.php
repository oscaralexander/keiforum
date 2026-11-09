<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $title;

    #[Computed]
    public function forums(): Collection
    {
        $forums = Forum::query()
            ->listed()
            ->orderBy('position')
            ->withCount(['topics' => fn ($query) => $query->visible()])
            ->get();

        $forums->each(function (Forum $forum): void {
            $forum->setRelation('recentTopics',
                $forum->topics()
                    ->visible()
                    ->where('is_pinned', false)
                    ->withCount('pollVotes', 'posts')
                    ->with('areas', 'latestPost.user', 'poll', 'posts.user')
                    ->orderByDesc(
                        DB::raw('(SELECT MAX(p.id) FROM posts p WHERE p.topic_id = topics.id)')
                    )
                    ->limit($forum->slug === 'algemeen' ? 10 : 5)
                    ->get()
                    ->each->setRelation('forum', $forum)
            );
        });

        return $forums;
    }

    /**
     * @return Collection<int, Area>
     */
    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount(['topics' => fn ($query) => $query->visible()])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function newestMembers(): Collection
    {
        return User::query()
            ->active()
            ->members()
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function upcomingActivities(): Collection
    {
        return Activity::query()
            ->published()
            ->upcoming()
            ->chronological()
            ->limit(5)
            ->get();
    }

    public function mount(): void
    {
        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = __('home.greeting_morning');
        } else if ($hour < 18) {
            $greeting = __('home.greeting_afternoon');
        } else {
            $greeting = __('home.greeting_evening');
        }

        if (auth()->check()) {
            $this->title = $greeting . ' ' . auth()->user()->first_name;
        } else {
            $this->title = __('home.title', ['greeting' => $greeting]);
        }
    }

    public function render()
    {
        return $this->view()
            ->title('De online huiskamer van Amersfoort');
    }

    #[Computed]
    public function schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'about' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Amersfoort',
                    'addressCountry' => 'NL',
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => 52.156111,
                    'longitude' => 5.387827,
                ],
                'name' => 'Amersfoort',
            ],
            'description' => 'De online huiskamer van Amersfoort.',
            'headline' => 'De online huiskamer van Amersfoort.',
            'name' => config('app.name'),
            'url' => url('/'),
            'publisher' => [
                '@type' => 'Organization',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('assets/img/keiforum-logo.svg'),
                ],
                'name' => config('app.name'),
            ],
        ];
    }
};

?>

@stack('head')

<div>
    <x-schema :data="$this->schema" />
    <x-header contrast hide-path :intro="__('home.intro')" :title="$title">
        <x-slot:actions>
            <x-btn class="m:hide" :href="route('topic.create')" icon="plus" primary small>@lang('forum/show.new_topic')</x-btn>
            <x-btn class="m:show" :href="route('topic.create')" icon="plus" primary>@lang('forum/show.new_topic')</x-btn>
        </x-slot:actions>
    </x-header>
    <div class="wrapper">
        <div class="sidebarLayout">
            <div class="sidebarLayout__main flex flex-col flex-gap-l">
                <div class="flex flex-col flex-gap-m">
                    @foreach ($this->forums as $forum)
                        <x-forum-list-item :$forum wire:key="forum-{{ $forum->id }}" />
                    @endforeach
                </div>
                <livewire:online-members />
            </div>
            <aside class="sidebarLayout__side">
                @if ($this->newestMembers->isNotEmpty())
                    <section class="sidebarLayout__section">
                        <h2 class="sidebarLayout__title"><a href="{{ route('members') }}" wire:navigate>@lang('home.newest_members')</a></h2>
                        <ul class="sidebarLayout__list">
                            @foreach ($this->newestMembers as $member)
                                <li class="member" wire:key="newest-member-{{ $member->id }}">
                                    <x-avatar :user="$member" />
                                    <div class="member__content">
                                        <a class="member__name" href="{{ route('member.show', $member) }}" wire:navigate>{{ $member->username }}</a>
                                        <ul class="meta">
                                            <li class="meta__item">{{ $member->created_at->translatedFormat('j M Y') }}</li>
                                        </ul>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if ($this->upcomingActivities->isNotEmpty())
                    <section class="sidebarLayout__section">
                        <h2 class="sidebarLayout__title"><a href="{{ route('agenda') }}" wire:navigate>@lang('home.upcoming_activities')</a></h2>
                        <ul class="sidebarLayout__list sidebarLayout__list--divided">
                            @foreach ($this->upcomingActivities as $activity)
                                <li wire:key="upcoming-activity-{{ $activity->id }}">
                                    <x-activity-list-item class="activityListItem--compact" :$activity is-date-visible />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                {{-- Hidden until more areas have content.
                <section class="sidebarLayout__section">
                    <h2 class="sidebarLayout__title">@lang('home.areas')</h2>
                    <ul class="sidebarLayout__links">
                        @foreach ($this->areas as $area)
                            <li wire:key="area-{{ $area->id }}">
                                <a href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>
                                <span class="sidebarLayout__count">{{ $area->topics_count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
                --}}
            </aside>
        </div>
    </div>
</div>