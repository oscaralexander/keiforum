@use('App\Enums\AvatarSize')

@props([
    'user',
])

<div {{ $attributes->class(['activityOrganizer']) }}>
    <a class="activityOrganizer__avatar" href="{{ route('member.show', $user) }}" tabindex="-1" wire:navigate>
        <x-avatar img-only :size="AvatarSize::M" :user="$user" />
    </a>
    <div class="activityOrganizer__text">
        <h5 class="activityOrganizer__label">@lang('agenda/show.organized_by')</h5>
        <h3 class="activityOrganizer__name">
            <a href="{{ route('member.show', $user) }}" wire:navigate>{{ $user->organizer_name }}</a>
        </h3>
        <div class="activityOrganizer__username">
            <a href="{{ route('member.show', $user) }}" wire:navigate>{{ '@'.$user->username }}</a>
        </div>
    </div>
</div>
