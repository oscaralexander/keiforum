@blaze(memo: true)

@props([
    'borderless' => false,
    'danger' => false,
    'good' => false,
    'href' => null,
    'icon' => null,
    'iconEnd' => false,
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
        'btn--good' => $good,
        'btn--icon' => $iconOnly,
        'btn--iconEnd' => $iconEnd,
        'btn--primary' => $primary,
        'btn--small' => $small,
        'btn--text' => $text,
    ]) }}
>
    @if ($icon && !$iconEnd)
        <x-icon :icon="$icon" />
    @endif
    @if (!empty($iconSlot))
        {{ $iconSlot }}
    @endif
    @if ($slot->isNotEmpty())
        <span>{{ $slot }}</span>
    @endif
    @if ($icon && $iconEnd)
        <x-icon :icon="$icon" />
    @endif
</{{ $tag }}>