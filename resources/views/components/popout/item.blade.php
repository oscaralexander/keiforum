@props([
    'danger' => false,
    'href' => null,
    'icon' => null,
    'label' => null,
    'navigate' => true,
])

@php
    $href = empty($href) ? '#' : $href;

    if ($attributes->has('wire:click')) {
        $click = $attributes->get('wire:click');
        $attributes = $attributes->except('wire:click')->merge([
            'wire:click.prevent' => $click,
        ]);
    }

    $attributes = $attributes->merge([
        'href' => $href,
        'wire:navigate' => $navigate
            && !$attributes->hasAny('wire:click', 'wire:click.prevent')
            && $attributes->missing('target'),
    ]);
@endphp

<li class="popout__menu-item">
    <a
        @class([
            'popout__menu-link',
            'popout__menu-link--danger' => $danger,
        ])
        {{ $attributes }}
    >
        @if($icon)
            <svg><use href="/assets/img/icons.svg#{{ $icon }}" /></svg>
        @endif
        <span>{{ $label }}</span>
    </a>
</li>