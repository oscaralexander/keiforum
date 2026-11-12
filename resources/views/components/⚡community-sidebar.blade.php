<?php

use App\Models\Activity;
use App\Models\Area;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * The key of the tip to show below the lists, see config/tips.php.
     * Guests are invited to register instead. Without a key, no tip shows.
     */
    public ?string $tip = null;

    /**
     * The topic the tip links to, unless the member already took part in it.
     * Guests get an invitation to register instead.
     */
    #[Computed]
    public function tipTopic(): ?Topic
    {
        $topicId = $this->tip ? config("tips.{$this->tip}.topic_id") : null;

        if (! $topicId) {
            return null;
        }

        return Topic::query()
            ->visible()
            ->with('forum')
            ->whereKey($topicId)
            ->when(auth()->check(), fn ($query) => $query->whereDoesntHave(
                'posts',
                fn ($query) => $query->whereBelongsTo(auth()->user())
            ))
            ->first();
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
            ->with('area')
            ->active()
            ->members()
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * When the member joined, in words for the last few days.
     */
    public function joinedOn(User $member): string
    {
        $daysAgo = (int) $member->created_at->copy()->startOfDay()->diffInDays(today());

        return match ($daysAgo) {
            0 => __('home.joined.today'),
            1 => __('home.joined.yesterday'),
            2 => __('home.joined.day_before_yesterday'),
            default => $member->created_at->translatedFormat('j M Y'),
        };
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
};
?>

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
                                @auth
                                    <li class="meta__item">{{ $member->area?->name ?? '—' }}</li>
                                @else
                                    <li class="meta__item">{{ $this->joinedOn($member) }}</li>
                                @endauth
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
    @if ($tip === null)
        {{-- No tips for now; pages opt in with the `tip` attribute. --}}
    @elseif (auth()->guest())
        <section class="sidebarLayout__section">
            <h2 class="sidebarLayout__title">@lang('tips.register.title')</h2>
            <p class="sidebarLayout__text">{!! __('tips.register.text', ['url' => e(route('register'))]) !!}</p>
        </section>
    @elseif ($this->tipTopic)
        <section class="sidebarLayout__section">
            <h2 class="sidebarLayout__title">@lang("tips.{$tip}.title")</h2>
            <p class="sidebarLayout__text">{!! __("tips.{$tip}.text", ['url' => e($this->tipTopic->url())]) !!}</p>
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
