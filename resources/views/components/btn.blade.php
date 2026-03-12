@blaze(memo: true)

@props([
    'borderless' => false,
    'danger' => false,
    'href' => null,
    'icon' => null,
    'navigate' => false,
    'primary' => false,
    'small' => false,
    'submit' => false,
    'text' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $iconOnly = $icon && $slot->isEmpty();
@endphp

<{{ $tag }}
    @if($href)
        href="{{ $href }}"
        role="button"
        @if ($navigate && $attributes->missing('target'))
            wire:navigate
        @endif
    @else
        type="{{ $submit ? 'submit' : 'button' }}"
    @endif
    {{ $attributes->class([
        'btn',
        'btn--borderless' => $borderless,
        'btn--danger' => $danger,
        'btn--icon' => $iconOnly,
        'btn--primary' => $primary,
        'btn--small' => $small,
        'btn--text' => $text,
    ]) }}
>
    @if ($icon)
        <x-icon :icon="$icon" />
    @endif
    @if (!empty($iconSlot))
        {{ $iconSlot }}
    @endif
    @if ($slot->isNotEmpty())
        <span>{{ $slot }}</span>
    @endif
</{{ $tag }}>