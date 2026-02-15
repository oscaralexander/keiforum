@props([
    'icon' => null,
])

<svg height="24" width="24"><use href="{{ asset('assets/img/icons.svg') }}#{{ $icon }}" /></svg>