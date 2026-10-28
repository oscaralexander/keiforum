<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->requestedOrganizer()
            ->with('area')
            ->oldest('organizer_requested_at')
            ->get();
    }

    public function approve(int $userId): void
    {
        $this->decide($userId, true);

        $this->dispatch('toast', message: __('admin/index.organizer_requests.approved'), type: 'success');
    }

    public function deny(int $userId): void
    {
        $this->decide($userId, false);

        $this->dispatch('toast', message: __('admin/index.organizer_requests.denied'), type: 'success');
    }

    private function decide(int $userId, bool $isApproved): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        User::query()->requestedOrganizer()->findOrFail($userId)->decideOrganizerRequest($isApproved);
        unset($this->users);
    }
};
?>

<section class="flex flex-col flex-gap-m">
    <h2>@lang('admin/index.organizer_requests.title')</h2>
    <div class="panel">
        @if ($this->users->isNotEmpty())
            <ol>
                @foreach ($this->users as $user)
                    <li class="headlineListItem" wire:key="organizer-request-{{ $user->id }}">
                        <div class="headlineListItem__text">
                            <a class="headlineListItem__title" href="{{ route('member.show', $user) }}" wire:navigate>{{ '@'.$user->username }}</a>
                            <ul class="meta">
                                <li class="meta__item">{{ $user->name }}</li>
                                @if ($user->area)
                                    <li class="meta__item">{{ $user->area->name }}</li>
                                @endif
                                <li class="meta__item">{{ time_diff($user->organizer_requested_at) }} @lang('ui.ago')</li>
                            </ul>
                        </div>
                        <div class="headlineListItem__actions">
                            <x-btn
                                :aria-label="__('admin/index.organizer_requests.approve')"
                                good
                                icon="check"
                                small
                                :title="__('admin/index.organizer_requests.approve')"
                                wire:click="approve({{ $user->id }})"
                            />
                            <x-btn
                                :aria-label="__('admin/index.organizer_requests.deny')"
                                danger
                                icon="x"
                                small
                                :title="__('admin/index.organizer_requests.deny')"
                                wire:click="deny({{ $user->id }})"
                                wire:confirm="{{ __('admin/index.organizer_requests.confirm_deny') }}"
                            />
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="panel--padded">@lang('admin/index.organizer_requests.empty')</p>
        @endif
    </div>
</section>
