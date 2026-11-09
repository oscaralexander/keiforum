<?php

use App\Models\Activity;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
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
