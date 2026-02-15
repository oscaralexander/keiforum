@props([
    'icon' => 'ellipsis',
    'small' => false,
])

<button
    aria-expanded="false"
    @class([
        'popout',
        'popout--small' => $small,
    ])
    x-bind:aria-expanded="isOpen"
    x-data="popout"
    x-on:click.outside="isOpen = false"
    {{ $attributes }}
>
    <x-icon :icon="$icon" />
    <ul class="popout__menu" x-ref="menu">
        {{ $slot }}
    </ul>
</button>