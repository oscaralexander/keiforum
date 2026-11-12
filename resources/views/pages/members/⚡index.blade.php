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
            ->active()
            ->members()
            ->with('area')
            ->orderBy('username', 'asc')
            ->get();
    }

    #[Computed]
    public function totalMembers(): int
    {
        return User::query()->active()->members()->count();
    }

    #[Computed]
    public function newMembers(): Collection
    {
        return User::query()
            ->active()
            ->members()
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function topPosters(): Collection
    {
        return User::query()
            ->active()
            ->members()
            ->where('username', '!=', 'keiforum')
            ->has('posts')
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('username')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return $this->view()
            ->title(__('members/index.title'));
    }
};
?>

<div>
    <x-header
        hide-path
        :intro="trans_choice('members/index.intro', $this->totalMembers, ['count' => number_format($this->totalMembers, 0, ',', '.')])"
        :title="__('members/index.title')"
    />
    <div class="wrapper">
        <div class="sidebarLayout">
            <div class="sidebarLayout__main">
                <div class="panel panel--padded">
                    <div class="members">
                        @foreach ($this->members as $member)
                            <div class="member" wire:key="member-{{ $member->id }}">
                                <x-avatar :user="$member" />
                                <div class="member__content">
                                    <a class="member__name" href="{{ route('member.show', $member) }}" wire:navigate>{{ $member->username }}</a>
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
            <aside class="sidebarLayout__side">
                @if ($this->newMembers->isNotEmpty())
                    <section class="sidebarLayout__section">
                        <h2 class="sidebarLayout__title">@lang('members/index.new_members.title')</h2>
                        <ul class="sidebarLayout__list">
                            @foreach ($this->newMembers as $member)
                                <li class="member" wire:key="new-member-{{ $member->id }}">
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
                <section class="sidebarLayout__section">
                    <h2 class="sidebarLayout__title">@lang('members/index.top_posters.title')</h2>
                    @if ($this->topPosters->isNotEmpty())
                        <ul class="sidebarLayout__list">
                            @foreach ($this->topPosters as $member)
                                <li class="member" wire:key="top-poster-{{ $member->id }}">
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
                    @else
                        <p class="text-color-lc">@lang('members/index.top_posters.empty')</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</div>
