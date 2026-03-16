<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    #[Computed]
    public function members(): Collection
    {
        return User::query()
            ->with('area')
            ->orderBy('username', 'asc')
            ->get();
    }

    #[Computed]
    public function totalMembers(): int
    {
        return User::count();
    }

    #[Computed]
    public function newMembersCount(): int
    {
        return User::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    #[Computed]
    public function latestMember(): ?User
    {
        return User::query()
            ->latest()
            ->first();
    }

    public function render()
    {
        return $this->view()
            ->title(__('members/index.title'));
    }
};
?>

<div>
    <x-header hide-path :title="__('members/index.title')" />
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-l">
            <div class="stats">
                <div class="stats__stat">
                    <h4 class="stats__stat-title">@lang('members/index.stats.members_count')</h4>
                    <p class="stats__stat-value">{{ $this->totalMembers }}</p>
                </div>
                <div class="stats__stat">
                    <h4 class="stats__stat-title">@lang('members/index.stats.members_count_week')</h4>
                    <p class="stats__stat-value">+{{ $this->newMembersCount }}</p>
                </div>
                <div class="stats__stat">
                    <h4 class="stats__stat-title">@lang('members/index.stats.latest_member')</h4>
                    <p class="stats__stat-value">
                        @if ($this->latestMember)
                            <a href="{{ route('member.show', $this->latestMember) }}">{{ $this->latestMember->username }}</a>
                        @else
                            <span class="text-color-lc">—</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="formatted">
                <p>
                    Keiforum is nog volop in ontwikkeling en ook dit onderdeel is nog lang niet klaar,
                    maar leuk dat je al even komt kijken!
                </p>
            </div>
            <div class="panel__outset panel__outset--padded">
                <div class="members">
                    @foreach ($this->members as $member)
                        <div class="memberGrid__item member">
                            <x-avatar :user="$member" />
                            <div class="member__content">
                                <a class="member__name" href="{{ route('member.show', $member) }}">{{ $member->username }}</a>
                                <ul class="meta">
                                    @auth
                                        <li class="meta__item">{{ $member->name }}</li>
                                        @if ($member->area)
                                            <li class="meta__item">{{ $member->area->name }}</li>
                                        @endif
                                    @endauth
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
