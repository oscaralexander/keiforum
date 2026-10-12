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
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
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
            ->limit(10)
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
    <x-header hide-path :title="__('members/index.title')" />
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-l">
            <div class="memberLists">
                <section class="memberLists__list">
                    <h3 class="memberLists__title">@lang('members/index.new_members.title')</h3>
                    @forelse ($this->newMembers as $member)
                        <div class="member" wire:key="new-member-{{ $member->id }}">
                            <x-avatar :user="$member" />
                            <div class="member__content">
                                <a class="member__name" href="{{ route('member.show', $member) }}">{{ $member->username }}</a>
                                <ul class="meta">
                                    <li class="meta__item">{{ $member->created_at->translatedFormat('j F Y') }}</li>
                                </ul>
                            </div>
                        </div>
                    @empty
                        <p class="memberLists__empty text-color-lc">@lang('members/index.new_members.empty')</p>
                    @endforelse
                </section>
                <section class="memberLists__list">
                    <h3 class="memberLists__title">@lang('members/index.top_posters.title')</h3>
                    @forelse ($this->topPosters as $member)
                        <div class="member" wire:key="top-poster-{{ $member->id }}">
                            <x-avatar :user="$member" />
                            <div class="member__content">
                                <a class="member__name" href="{{ route('member.show', $member) }}">{{ $member->username }}</a>
                                <ul class="meta">
                                    <li class="meta__item">{{ trans_choice('members/index.top_posters.posts_count', $member->posts_count, ['count' => $member->posts_count]) }}</li>
                                </ul>
                            </div>
                        </div>
                    @empty
                        <p class="memberLists__empty text-color-lc">@lang('members/index.top_posters.empty')</p>
                    @endforelse
                </section>
            </div>
            <h3>@lang('members/index.all_members', ['count' => $this->totalMembers])</h3>
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
