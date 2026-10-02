<?php

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/unsubscribe_digest.title'));
    }

    public function resubscribe(): void
    {
        $this->user->update(['is_subscribed_to_digest' => true]);
    }

    public function unsubscribe(): void
    {
        $this->user->update(['is_subscribed_to_digest' => false]);
    }
};
?>

<div>
    <x-header
        center
        hide-path
        :intro="__($user->is_subscribed_to_digest ? 'user/unsubscribe_digest.subscribed_text' : 'user/unsubscribe_digest.unsubscribed_text')"
        :title="__('user/unsubscribe_digest.title')"
    />
    <div class="flex flex-justify-center">
        @if ($user->is_subscribed_to_digest)
            <x-btn primary wire:click="unsubscribe">@lang('user/unsubscribe_digest.unsubscribe')</x-btn>
        @else
            <x-btn text wire:click="resubscribe">@lang('user/unsubscribe_digest.resubscribe')</x-btn>
        @endif
    </div>
</div>
