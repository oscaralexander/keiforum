@use('App\Enums\AvatarSize')

@props([
    'conversation' => null,
])

<header class="conversation__header">
    <div class="conversation__header-inner">
        <div class="conversation__participants">
            @foreach ($conversation->otherParticipants() as $participant)
                <a class="conversation__participant" href="{{ route('user.show', $participant) }}" wire:navigate>
                    <x-avatar class="conversation__participant-avatar" :size="AvatarSize::S" :user="$participant" />
                    {{ $participant->username }}
                </a>
            @endforeach
        </div>
        <div class="conversation__header-actions">
            <x-popout>
                <x-popout.item icon="trash" danger :label="__('ui.delete')" />
            </x-popout>
        </div>
    </div>
</header>