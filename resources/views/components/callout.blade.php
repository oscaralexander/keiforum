@blaze

@props([
    'icon' => null,
    'success' => false,
    'text' => null,
    'title' => null,
])

<div
    {{ $attributes->class([
        'callout',
        'callout--success' => $success,
    ]) }}
 >
    <x-icon class="callout__icon" :$icon />
    <div class="callout__content">
        @if ($title)
            <h4 class="callout__title">{{ $title }}</h4>
        @endif
        <p class="callout__text">{{ $text }}</p>
    </div>
</div>