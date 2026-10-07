<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function members(): Collection
    {
        return User::query()
            ->active()
            ->members()
            ->online()
            ->orderBy('username')
            ->get();
    }
};
?>

<div>
    @if ($this->members->isNotEmpty())
        <p class="onlineMembers">
            {{ trans_choice('members/online.title', $this->members->count()) }}:
            @foreach ($this->members as $member)
                <a class="onlineMembers__member" href="{{ route('member.show', $member) }}" wire:key="online-member-{{ $member->id }}" wire:navigate>{{ $member->username }}</a>@if (!$loop->last), @endif
            @endforeach
        </p>
    @endif
</div>
