@blaze(memo: true)

@props([
    'icon' => null,
])

<svg {{ $attributes->class('icon') }} height="24" width="24" {{ $attributes }}>
    <use href="{{ asset('assets/img/icons.svg') }}#{{ $icon }}" />
</svg>